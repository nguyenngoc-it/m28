<?php

namespace Modules\KiotViet\Commands;

use App\Base\CommandBus;
use Carbon\Carbon;
use Gobiz\Log\LogService;
use Gobiz\Validation\ValidationException;
use Gobiz\Workflow\WorkflowException;
use Illuminate\Support\Arr;
use Modules\Marketplace\Services\Marketplace;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\KiotViet\Services\KiotViet;
use Modules\Order\Resource\Data3rdResource;
use Modules\Product\Models\Sku;
use Modules\Store\Models\Store;
use Modules\Store\Models\StoreSku;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;

class SyncKiotVietOrder extends CommandBus
{
    /**
     * @var Store
     */
    protected $store;

    /**
     * @var array
     */
    protected $input;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SyncKiotVietOrderJob constructor
     *
     * @param Store $store
     * @param array $input Thông tin order theo response của KiotViet api /orders/detail
     * @param User $creator
     */
    public function __construct(Store $store, array $input)
    {
        $this->store   = $store;
        $this->input   = $input;
        $this->creator = Service::user()->getSystemUserDefault();

        $this->logger = LogService::logger('KiotViet', [
            'context' => ['shop_id' => $store->marketplace_store_id],
        ]);
    }

    /**
     * @return Order
     * @throws ValidationException
     * @throws WorkflowException
     */
    public function handle()
    {
        $orderAmount = 0;
        $eventName   = data_get($this->input, '__event');
        $event       = $this->getEvent($eventName);

        $kiotVietStatus = data_get($this->input, 'status', Order::STATUS_WAITING_INSPECTION);

        // Trạng thái đơn:
        // 1 - Phiếu tạm
        // 2 - Đang giao hàng
        // 3 - Hoàn thành
        // 4 - Đã huỷ
        // 5 - Đã xác nhận

        $listStatus = [
            KiotViet::ORDER_STATUS_WAITING_INSPECTION => Order::STATUS_WAITING_INSPECTION,
            KiotViet::ORDER_STATUS_DELIVERING         => Order::STATUS_DELIVERING,
            // KiotViet::ORDER_STATUS_FINISH             => Order::STATUS_FINISH,
            KiotViet::ORDER_STATUS_CANCELED           => Order::STATUS_CANCELED,
            KiotViet::ORDER_STATUS_WAITING_CONFIRM    => Order::STATUS_WAITING_PROCESSING,
        ];

        // Nếu update trạng thái vận chuyển qua hoá đơn thì mapping trạng thái qua thông tin hoá đơn
        if ($eventName == KiotViet::WEBHOOK_INVOICE_STATUS_UPDATE) {
            // Lấy thông tin order của hoá đơn này
            $invoiceId     = data_get($this->input, 'id');
            $invoiceDetail = Service::kiotviet()->findInvoice($invoiceId, $this->store);
            $orderCode     = data_get($invoiceDetail, 'orderCode');
            if ($orderCode) {
                $this->input['code'] = $orderCode;
            }
        }

        $status = Arr::get($listStatus, $kiotVietStatus, Order::STATUS_WAITING_INSPECTION);

        $orderAmount = 0;

        $itemSkus = [];

        $items = data_get($this->input, "{$event}Details", []);

        foreach ($items as $item) {

            $price    = data_get($item, 'price');
            $quantity = data_get($item, 'quantity');
            $totalAmount = (float) $price * (int) $quantity;
            $orderAmount += $totalAmount;

            $discountAmount = (float) data_get($item, 'discount');
            $discountRatio  = data_get($item, 'discountRatio');

            if ($discountRatio) {
                $discountAmount = $totalAmount * (int) $discountRatio / 100;
            }

            // Check Sku Đã tồn tại trên hệ thống chưa

            $productId = data_get($item, 'productId');
            $skuCode   = data_get($item, 'productCode');
            $sku = Sku::where('code', $skuCode)->first();

            $storeSku = Service::store()->getStoreSkuOnSell($this->store, $productId, $skuCode);

            if (!$storeSku) {
                // Tạo mới product
                (new SyncKiotVietProduct($this->store, $productId))->handle();
            }

            $itemSkus[] = [
                'id_origin'       => $productId,
                'code'            => $skuCode,
                'discount_amount' => $discountAmount,
                'price'           => data_get($item, 'price'),
                'quantity'        => data_get($item, 'quantity'),
            ];
        }

        $shippingAmount = (float)data_get($this->input, "{$event}Delivery.price", 0);
        $totalAmount    = (float)data_get($this->input, 'total', 0);
        $discountAmount = $orderAmount - $totalAmount;

        $usingCod = data_get($this->input, 'usingCod', 0);

        // Make Order
        $dataResource = new Data3rdResource();

        $dataResource->receiver = [
            'name'    => data_get($this->input, "{$event}Delivery.receiver"),
            'phone'   => data_get($this->input, "{$event}Delivery.contactNumber"),
            'address' => data_get($this->input, "{$event}Delivery.address") 
                        . ' - ' . data_get($this->input, "{$event}Delivery.wardName") 
                        . ' - ' . data_get($this->input, "{$event}Delivery.locationName"),
        ];
        $dataResource->marketplace_code     = Marketplace::CODE_KIOTVIET;
        $dataResource->id                   = data_get($this->input, 'id');
        $dataResource->code                 = data_get($this->input, 'code');
        $dataResource->order_amount         = $orderAmount;
        $dataResource->shipping_amount      = $shippingAmount;
        $dataResource->discount_amount      = abs($discountAmount);
        $dataResource->total_amount         = $totalAmount;
        $dataResource->freight_bill         = data_get($this->input, "{$event}Delivery.deliveryCode");
        $dataResource->intended_delivery_at = Carbon::parse(data_get($this->input, "{$event}Delivery.expectedDelivery"));
        $dataResource->created_at_origin    = Carbon::parse(data_get($this->input, 'createdDate'));
        $dataResource->using_cod            = $usingCod;
        $dataResource->status               = $status;
        $dataResource->shipping_partner = [
            'id'   => data_get($this->input, "{$event}Delivery.partnerDeliveryId"),
            'name' => data_get($this->input, "{$event}Delivery.partnerDelivery.name"),
        ];
        $dataResource->items = $itemSkus;

        $orderData = Service::order()->createOrderFrom3rdPartner($this->store, $dataResource);

        return $orderData;
    }

    /**
     * [getEvent]
     * @param string $eventName
     * @return string $event
     */
    protected function getEvent(string $eventName)
    {
        switch ($eventName) {
            case KiotViet::WEBHOOK_ORDER_STATUS_UPDATE:
                $event = 'order';
                break;

            case KiotViet::WEBHOOK_INVOICE_STATUS_UPDATE:
                $event = 'invoice';
                break;

            default:
                $event = '';
                break;
        }

        return $event;
    }
}
