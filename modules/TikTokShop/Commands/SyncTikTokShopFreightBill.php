<?php

namespace Modules\TikTokShop\Commands;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Modules\FreightBill\Models\FreightBill;
use Modules\Marketplace\Services\Marketplace;
use Modules\Order\Models\Order;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Service;
use Modules\Store\Models\Store;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;

class SyncTikTokShopFreightBill
{
    /**
     * @var Store
     */
    protected $store;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var string
     */
    protected $trackingNo;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SyncShopeeOrderFreightBill constructor
     *
     * @param Store $store
     * @param Order $order
     * @param string $trackingNo
     * @param User $creator
     */
    public function __construct(Store $store, $order, $trackingNo, User $creator = null)
    {
        $this->store      = $store;
        $this->order      = $order;
        $this->trackingNo = $trackingNo;
        $this->creator    = $creator ?: Service::user()->getSystemUserDefault();

        $this->logger = LogService::logger('tiktokshop', [
            'context' => ['shop_id' => $store->marketplace_store_id, 'order_code' => $order->code, 'tracking_no' => $trackingNo],
        ]);
    }

    /**
     * @return FreightBill[]
     * @throws RestApiException
     */
    public function handle()
    {
        $orders = Order::query()->where([
            'marketplace_code' => Marketplace::CODE_TIKTOKSHOP,
            'code'             => $this->order->code,
        ])->with(['orderPackings.freightBill'])->get();
        $freightBills = [];
        foreach ($orders as $order) {
            if ($freightBill = $this->sync($order)) {
                $freightBills[] = $freightBill;
            }
        }

        return $freightBills;
    }

    /**
     * @param Order $order
     * @return FreightBill|null
     * @throws RestApiException
     */
    protected function sync(Order $order)
    {
        $logisticsStatus = $this->getLogisticsStatus();
        if (!$orderPacking = $this->detectOrderPacking($order)) {
            return null;
        }

        $freightBill = !$orderPacking->freight_bill_id
            ? $this->createFreightBill($orderPacking)
            : $orderPacking->freightBill;

        $this->syncFreightBillStatus($freightBill);

        return $freightBill;
    }

    /**
     * Tìm ycđh tương ứng cho mvđ
     *
     * @param Order $order
     * @return OrderPacking|null
     */
    protected function detectOrderPacking(Order $order)
    {
        /**
         * @var OrderPacking $orderPacking
         */
        $activeOrderPackings = $order->orderPackings->where('status', '!=', OrderPacking::STATUS_CANCELED);

        // ycđh có mvđ là mvđ hiện tại
        if ($orderPacking = $activeOrderPackings->firstWhere('freightBill.freight_bill_code', $this->trackingNo)) {
            return $orderPacking;
        }

        // order chỉ được phép có 1 ycđh thì mới xác định tự động ycđh được
        $count = $activeOrderPackings->count();
        if ($count !== 1) {
            $this->logger->info('ORDER_HAD_MANY_ORDER_PACKINGS', ['order_packings' => $count]);
            return null;
        }

        // ycđh chưa được tạo mvđ
        $orderPacking = $activeOrderPackings->first();
        if (!$orderPacking->freight_bill_id) {
            return $orderPacking;
        }

        $this->logger->info('FREIGHT_BILL_NOT_BELONG_TO_ORDER_PACKING', ['order_packing_id' => $orderPacking->id]);

        return null;
    }

    /**
     * @param OrderPacking $orderPacking
     * @return FreightBill|object
     */
    protected function createFreightBill(OrderPacking $orderPacking)
    {
        /**
         * @var FreightBill $freightBill
         */
        $freightBill = FreightBill::query()->updateOrCreate([
            'shipping_partner_id' => $orderPacking->shipping_partner_id,
            'freight_bill_code' => $this->trackingNo,
        ], [
            'tenant_id' => $orderPacking->tenant_id,
            'order_id' => $orderPacking->order_id,
            'order_packing_id' => $orderPacking->id,
            'status' => FreightBill::STATUS_WAIT_FOR_PICK_UP,
            'snapshots' => Service::orderPacking()->makeSnapshots($orderPacking),
            'cod_total_amount' => $orderPacking->order->cod
        ]);

        $orderPacking->updateFreightBill($freightBill);

        return $freightBill;
    }

    /**
     * @param FreightBill $freightBill
     * @return FreightBill
     * @throws RestApiException
     */
    protected function syncFreightBillStatus(FreightBill $freightBill)
    {
        $logisticsStatus = $this->getLogisticsStatus();

        if (!$logisticsStatus || !$status = Service::tikTokShop()->mapFreightBillStatus($logisticsStatus)) {
            $this->logger->info('CANT_MAPPING_LOGISTICS_STATUS', ['logistics_status' => $logisticsStatus]);
            return $freightBill;
        }
        return $freightBill->changeStatus($status, $this->creator);
    }

    /**
     * @return string
     * @throws RestApiException
     */
    protected function getLogisticsStatus()
    {

        $logisticsMessage = null;

        $tikTokShopApi = Service::tikTokShop()->api();

        // Lấy thông tin package_id
        $paramsRequest = [
            'shop_id'      => $this->store->marketplace_store_id,
            'order_id'     => $this->order->code,
            'access_token' => $this->store->getSetting('access_token')
        ];
        $orderDetail = $tikTokShopApi->getOrderDetails($paramsRequest)->getData('data.order_list');
        $orderDetail = $orderDetail[0];
        $packageList = data_get($orderDetail, 'package_list', []);
        if ($packageList) {
            foreach ($packageList as $package) {
                $packageId = data_get($package, 'package_id');
                $paramsRequest = [
                    'shop_id'      => $this->store->marketplace_store_id,
                    'package_id'   => $packageId,
                    'access_token' => $this->store->getSetting('access_token')
                ];
                $logisticsMessage = $tikTokShopApi->getLogisticsMessage($paramsRequest)->getData('data.package_status');
            }
        }

        return $logisticsMessage;
    }
}
