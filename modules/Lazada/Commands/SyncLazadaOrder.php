<?php

namespace Modules\Lazada\Commands;

use App\Base\CommandBus;
use Carbon\Carbon;
use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use Modules\Lazada\Services\LazadaApiInterface;
use Modules\Marketplace\Services\Marketplace;
use Modules\Marketplace\Services\MarketplaceException;
use Modules\Order\Models\Order;
use Modules\Service;
use Modules\Lazada\Services\Lazada;
use Modules\Store\Models\Store;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;
use Modules\Order\Resource\Data3rdResource;
use Modules\Store\Models\StoreSku;

class SyncLazadaOrder extends CommandBus
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
     * @var LazadaApiInterface
     */
    protected $api;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected $orderItemsData = [];

    /**
     * SyncLazadaOrderJob constructor
     *
     * @param Store $store
     * @param array $input Thông tin order theo response của Lazada api /orders/detail
     */
    public function __construct(Store $store, array $input)
    {
        $this->store   = $store;
        $this->input   = $input;
        $this->api     = Service::lazada()->api();
        $this->creator = Service::user()->getSystemUserDefault();

        $this->logger = LogService::logger('lazada', [
            'context' => ['shop_id' => $store->marketplace_store_id],
        ]);
    }

    /**
     * @return Order
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function handle()
    {
        //Lấy dữ liệu order từ API Lazada
        $orderId        = data_get($this->input, 'order_id');
        $accessToken    = $this->store->getSetting('access_token');
        $paramsRequest  = [
            'order_id' => $orderId,
            'access_token' => $accessToken,
        ];
        $orderData      = $this->api->getOrderDetails($paramsRequest)->getData('data');
        $orderItemsData = $this->api->getOrderItemDetails($paramsRequest)->getData('data');

        // dd($orderData, $orderItemsData);
        $lazadaStatus = data_get($this->input, 'order_status', '');
        $status       = $this->mapOrderStatus($lazadaStatus);

        $itemSkus         = [];
        $shippingPartners = [];
        foreach ($orderItemsData as $item) {

            $price = (float)data_get($item, 'item_price');

            $discountAmount = (float)data_get($item, 'voucher_amount');

            // Check Sku Đã tồn tại trên hệ thống chưa
            $productId = data_get($item, 'product_id');
            $skuId     = data_get($item, 'sku_id');
            $skuCode   = data_get($item, 'sku');

            $storeSku = Service::store()->getStoreSkuOnSell($this->store, $skuId, $skuCode);

            if (!$storeSku) {
                // Tạo mới product
                (new SyncLazadaProduct($this->store, $productId))->handle();
            }

            $itemSkus[$skuId]['id_origin'] = $skuId;
            $itemSkus[$skuId]['code']      = $skuCode;
            $itemSkus[$skuId]['price']     = $price;

            if (!isset($itemSkus[$skuId]['quantity'])) {
                $itemSkus[$skuId]['quantity'] = 1;
            } else {
                $itemSkus[$skuId]['quantity'] += 1;
            }

            if (!isset($itemSkus[$skuId]['discount_amount'])) {
                $itemSkus[$skuId]['discount_amount'] = $discountAmount;
            } else {
                $itemSkus[$skuId]['discount_amount'] += $discountAmount;
            }

            $shippingPartners = [
                'carrier_key' => data_get($item, 'shipment_provider'),
                'carrier_name' => data_get($item, 'shipment_provider')
            ];

        }

        $shippingPartner = $this->makeShippingPartner($shippingPartners);

        $orderAmount    = (float)data_get($orderData, 'price');
        $discountAmount = (float)data_get($orderData, 'voucher');
        $totalAmount    = $orderAmount - $discountAmount;
        $shippingAmount = data_get($orderData, 'shipping_fee');

        $receiverAddress = data_get($orderData, 'address_shipping.address1');
        $receiverCity    = data_get($orderData, 'address_shipping.city');
        $receiverCountry = data_get($orderData, 'address_shipping.country');

        $usingCod = data_get($orderData, 'payment_method');
        if ($usingCod == 'COD') {
            $usingCod = true;
        } else {
            $usingCod = false;
        }

        $freightBillCodeOrder = '';
        if ($orderItemsData) {
            foreach ($orderItemsData as $orderItem) {
                $freightBillCodeOrder = data_get($orderItem, 'tracking_code');
            }
        }

        // Make Order
        $dataResource = new Data3rdResource();

        $dataResource->receiver             = [
            'name' => data_get($orderData, "address_shipping.first_name") . data_get($orderData, "address_shipping.last_name"),
            'phone' => data_get($orderData, "address_shipping.phone"),
            'address' => "{$receiverAddress} - {$receiverCity} - {$receiverCountry}",
        ];
        $dataResource->marketplace_code     = Marketplace::CODE_LAZADA;
        $dataResource->id                   = data_get($this->input, 'order_id');
        $dataResource->code                 = data_get($orderData, "order_number");
        $dataResource->order_amount         = $orderAmount;
        $dataResource->shipping_amount      = $shippingAmount;
        $dataResource->discount_amount      = abs($discountAmount);
        $dataResource->total_amount         = $totalAmount;
        $dataResource->freight_bill         = $freightBillCodeOrder;
        $dataResource->intended_delivery_at = '';
        $dataResource->created_at_origin    = Carbon::parse(data_get($orderData, 'created_at'));
        $dataResource->using_cod            = $usingCod;
        $dataResource->status               = $status;
        $dataResource->items                = $itemSkus;
        $dataResource->shipping_partner     = [
            'id' => $shippingPartner['carrier_key'],
            'name' => $shippingPartner['carrier_name'],
        ];

        $orderData = Service::order()->createOrderFrom3rdPartner($this->store, $dataResource);

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
            Lazada::ORDER_STATUS_UNPAID => Order::STATUS_WAITING_CONFIRM,
            Lazada::ORDER_STATUS_PENDING => Order::STATUS_WAITING_PROCESSING,
            Lazada::ORDER_STATUS_PACKED => Order::STATUS_WAITING_PACKING,
            Lazada::ORDER_STATUS_READY_TO_SHIP_PENDING => Order::STATUS_WAITING_PICKING,
            Lazada::ORDER_STATUS_READY_TO_SHIP => Order::STATUS_WAITING_PICKING,
            Lazada::ORDER_STATUS_SHIPPED => Order::STATUS_DELIVERING,
            Lazada::ORDER_STATUS_DELIVERED => Order::STATUS_DELIVERED,
            Lazada::ORDER_STATUS_FAILED_DELIVERY => Order::STATUS_FAILED_DELIVERY,
            Lazada::ORDER_STATUS_LOST_BY_3PL => Order::STATUS_FAILED_DELIVERY,
            Lazada::ORDER_STATUS_DAMAGED_BY_3PL => Order::STATUS_FAILED_DELIVERY,
            Lazada::ORDER_STATUS_RETURNED => Order::STATUS_RETURN_COMPLETED,
            Lazada::ORDER_STATUS_CANCELED => Order::STATUS_CANCELED,
            Lazada::ORDER_STATUS_SHIPPED_BACK => Order::STATUS_RETURN,
            Lazada::ORDER_STATUS_SHIPPED_BACK_SUCCESS => Order::STATUS_RETURN_COMPLETED,
            Lazada::ORDER_STATUS_SHIPPED_BACK_FAILED => Order::STATUS_RETURN
        ];

        $status = Arr::get($listStatus, $tikiStatus, Order::STATUS_WAITING_INSPECTION);

        return $status;
    }

    /**
     * @param array $dataCarrier
     * @return array
     */
    protected function makeShippingPartner(array $dataCarrier)
    {
        $logistic                   = [
            'carrier_key' => '',
            'carrier_name' => ''
        ];
        $checkoutShippingCarrierKey = Arr::get($dataCarrier, "carrier_key");
        $checkoutShippingCarrier    = Arr::get($dataCarrier, "carrier_name");

        // Clear
        $checkoutShippingCarrierKey = Str::before($checkoutShippingCarrierKey, ',');
        $checkoutShippingCarrierKey = Str::after($checkoutShippingCarrierKey, ':');
        $checkoutShippingCarrierKey = strtolower(Str::slug($checkoutShippingCarrierKey, '_'));

        $checkoutShippingCarrier = Str::before($checkoutShippingCarrier, ',');
        $checkoutShippingCarrier = Str::after($checkoutShippingCarrier, ':');

        if ($checkoutShippingCarrierKey) {
            $logistic = [
                'carrier_key' => trim($checkoutShippingCarrierKey),
                'carrier_name' => trim($checkoutShippingCarrier)
            ];
        }

        return $logistic;
    }

}
