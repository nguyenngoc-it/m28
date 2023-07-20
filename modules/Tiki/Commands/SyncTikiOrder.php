<?php

namespace Modules\Tiki\Commands;

use App\Base\CommandBus;
use Carbon\Carbon;
use Gobiz\Log\LogService;
use Gobiz\Validation\ValidationException;
use Gobiz\Workflow\WorkflowException;
use Illuminate\Support\Arr;
use Modules\Marketplace\Services\Marketplace;
use Modules\Order\Models\Order;
use Modules\Order\Resource\Data3rdResource;
use Modules\Service;
use Modules\Tiki\Services\Tiki;
use Modules\Product\Models\Sku;
use Modules\Store\Models\Store;
use Modules\Store\Models\StoreSku;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;

class SyncTikiOrder extends CommandBus
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
     * SyncTikiOrderJob constructor
     *
     * @param Store $store
     * @param array $input Thông tin order theo response của Tiki api /orders/detail
     * @param User $creator
     */
    public function __construct(Store $store, array $input)
    {
        $this->store   = $store;
        $this->input   = $input;
        $this->creator = Service::user()->getSystemUserDefault();
        $this->api     = Service::tiki()->api();

        $this->logger = LogService::logger('tiki-sync-order-input', [
            'context' => ['shop_id' => $store->marketplace_store_id],
        ]);
    }

    /**
     * @return Order|null
     * @throws ValidationException
     * @throws WorkflowException
     */
    public function handle()
    {
        // Get Data Order Detail From Tiki
        $orderId = data_get($this->input, 'order_id');

        $paramsRequest = [
            'order_id'     => $orderId,
            'access_token' => $this->store->getSetting('access_token')
        ];

        $orderDetail = $this->api->getOrderDetails($paramsRequest)->getData();
        $this->logger->info('data-from-tiki', $orderDetail);
        // dd($orderDetail);
        $orderAmount = 0;

        $tikiStatus = data_get($orderDetail, 'status', '');

        $status = $this->mapOrderStatus($tikiStatus);

        $orderAmount = 0;

        $itemSkus = [];

        $items = data_get($orderDetail, "items", []);

        // dd($orderDetail);

        foreach ($items as $item) {

            $price    = data_get($item, 'price');
            $quantity = data_get($item, 'qty');
            $totalAmount = (float) $price * (int) $quantity;
            $orderAmount += $totalAmount;

            $discountAmount = (float) data_get($item, 'invoice.discount_amount');

            // Check Sku Đã tồn tại trên hệ thống chưa

            $productId = data_get($item, 'product.id');
            $skuCode   = data_get($item, 'product.sku');
            $storeSku  = Service::store()->getStoreSkuOnSell($this->store, $productId, $skuCode);

            if (!$storeSku) {
                // Tạo mới product
                (new SyncTikiProduct($this->store, $productId))->handle();
            }

            $itemSkus[] = [
                'id_origin'       => $productId,
                'code'            => $skuCode,
                'discount_amount' => $discountAmount,
                'price'           => $price,
                'quantity'        => $quantity,
            ];
        }

        $shippingAmount = (float)data_get($orderDetail, "invoice.shipping_amount_after_discount", 0);
        $totalAmount    = (float)data_get($orderDetail, 'invoice.grand_total', 0);
        $discountAmount = $orderAmount - $totalAmount;

        $usingCod = data_get($orderDetail, 'payment.method', '');
        if ($usingCod == 'cod') {
            $usingCod = true;
        } else {
            $usingCod = false;
        }

        // Make Order
        $dataResource = new Data3rdResource();

        $dataResource->receiver = [
            'name'    => data_get($orderDetail, "shipping.address.full_name"),
            'phone'   => data_get($orderDetail, "shipping.address.phone"),
            'address' => data_get($orderDetail, "shipping.address.street") 
                       . ' - ' . data_get($orderDetail, "shipping.address.ward") 
                       . ' - ' . data_get($orderDetail, "shipping.address.district") 
                       . ' - ' . data_get($orderDetail, "shipping.address.region") 
                       . ' - ' . data_get($orderDetail, "shipping.address.country"),
        ];
        $dataResource->marketplace_code     = Marketplace::CODE_TIKI;
        $dataResource->id                   = data_get($orderDetail, "id");
        $dataResource->code                 = data_get($orderDetail, "code");
        $dataResource->order_amount         = $orderAmount;
        $dataResource->shipping_amount      = $shippingAmount;
        $dataResource->discount_amount      = abs($discountAmount);
        $dataResource->total_amount         = $totalAmount;
        $dataResource->freight_bill         = data_get($orderDetail, "shipping.tracking_code");
        $dataResource->intended_delivery_at = Carbon::parse(data_get($orderDetail, "shipping.plan.promised_delivery_date"));
        $dataResource->created_at_origin    = Carbon::parse(data_get($orderDetail, 'created_at'));
        $dataResource->using_cod            = $usingCod;
        $dataResource->status               = $status;
        $dataResource->items                = $itemSkus;

        // dd($dataResource);

        if (!$dataResource->code) {
            $orderData = null;
            $this->logger->error('order-not-code', $orderDetail);
        } else {
            $orderData = Service::order()->createOrderFrom3rdPartner($this->store, $dataResource);   
        }

        return $orderData;
    }

    /**
     * Map Order Status From Tiki
     *
     * @param string $tikiStatus
     * @return string
     */
    protected function mapOrderStatus(string $tikiStatus)
    {
        $listStatus = [
            Tiki::ORDER_STATUS_WAITING_INSPECTION  => Order::STATUS_WAITING_INSPECTION,
            Tiki::ORDER_STATUS_CANCELED            => Order::STATUS_CANCELED,
            Tiki::ORDER_STATUS_COMPLETE            => Order::STATUS_FINISH,
            Tiki::ORDER_STATUS_DELIVERY_SUCCESS    => Order::STATUS_DELIVERED,
            Tiki::ORDER_STATUS_PROCESSING          => Order::STATUS_WAITING_PROCESSING,
            Tiki::ORDER_STATUS_WAITING_PAYMENT     => Order::STATUS_WAITING_CONFIRM,
            Tiki::ORDER_STATUS_HANDOVER_TO_PARTNER => Order::STATUS_WAITING_DELIVERY,
            Tiki::ORDER_STATUS_CLOSED              => Order::STATUS_CANCELED,
            Tiki::ORDER_STATUS_PACKAGING           => Order::STATUS_WAITING_PACKING,
            Tiki::ORDER_STATUS_PICKING             => Order::STATUS_WAITING_PICKING,
            Tiki::ORDER_STATUS_SHIPPING            => Order::STATUS_DELIVERING,
            Tiki::ORDER_STATUS_PAID                => Order::STATUS_WAITING_PROCESSING,
            Tiki::ORDER_STATUS_DELIVERD            => Order::STATUS_DELIVERED,
            Tiki::ORDER_STATUS_HOLDED              => Order::STATUS_WAITING_DELIVERY,
            Tiki::ORDER_STATUS_READY_TO_SHIP       => Order::STATUS_WAITING_DELIVERY,
            Tiki::ORDER_STATUS_PAYMENT_REVIEW      => Order::STATUS_WAITING_PROCESSING,
            Tiki::ORDER_STATUS_RETURNED            => Order::STATUS_RETURN_COMPLETED,
            Tiki::ORDER_STATUS_FINISHED_PACKING    => Order::STATUS_WAITING_DELIVERY,
        ];

        $status = Arr::get($listStatus, $tikiStatus, Order::STATUS_WAITING_INSPECTION);

        return $status;
    }
}
