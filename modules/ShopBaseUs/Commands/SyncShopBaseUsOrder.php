<?php

namespace Modules\ShopBaseUs\Commands;

use App\Base\CommandBus;
use Carbon\Carbon;
use Gobiz\Log\LogService;
use Gobiz\Validation\ValidationException;
use Gobiz\Workflow\WorkflowException;
use Illuminate\Support\Arr;
use Modules\Currency\Models\Currency;
use Modules\Marketplace\Services\Marketplace;
use Modules\Order\Models\Order;
use Modules\Order\Resource\Data3rdResource;
use Modules\Service;
use Modules\ShopBaseUs\Services\ShopBaseUs;
use Modules\Product\Models\Sku;
use Modules\Store\Models\Store;
use Modules\Store\Models\StoreSku;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;

class SyncShopBaseUsOrder extends CommandBus
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
     * SyncShopBaseUsOrderJob constructor
     *
     * @param Store $store
     * @param array $input Thông tin order theo response của ShopBaseUs api /orders/detail
     * @param User $creator
     */
    public function __construct(Store $store, array $input)
    {
        $this->store   = $store;
        $this->input   = $input;
        $this->creator = Service::user()->getSystemUserDefault();
        $this->api     = Service::shopBaseUs()->api();

        $this->logger = LogService::logger('shopbaseus', [
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
        // Get Data Order Detail From ShopBaseUs
        $orderId = data_get($this->input, 'order_id');

        $paramsRequest = [
            'order_id'      => $orderId,
            'shop_name'     => $this->store->getSetting('shop_name'),
            'client_id'     => $this->store->getSetting('client_id'),
            'client_secret' => $this->store->getSetting('client_secret'),
        ];

        $orderDetail = $this->api->getOrderDetails($paramsRequest)->getData('order');
        // dd($orderDetail);
        $orderAmount = 0;

        $orderAmount = 0;

        $itemSkus = [];

        $items = data_get($orderDetail, "line_items", []);

        // dd($items);

        foreach ($items as $item) {

            $price    = data_get($item, 'price');
            $quantity = data_get($item, 'quantity');
            $totalAmount = (float) $price * (int) $quantity;
            $orderAmount += $totalAmount;

            $discountAmount = (float) data_get($item, 'discount_amount');

            // Check Sku Đã tồn tại trên hệ thống chưa

            $productId = data_get($item, 'product_id');
            $skuId     = data_get($item, 'variant_id');
            $sellerSku = data_get($item, 'sku');
            if ($sellerSku) {
                $skuCode = $sellerSku;
            } else {
                $skuCode = $skuId;   
            }
            $storeSku = Service::store()->getStoreSkuOnSell($this->store, $skuId, $skuCode);

            if (!$storeSku) {
                // Tạo mới product
                (new SyncShopBaseUsProduct($this->store, $productId))->handle();
            }

            $itemSkus[] = [
                'id_origin'       => $skuId,
                'code'            => $skuCode,
                'discount_amount' => $discountAmount,
                'price'           => $price,
                'quantity'        => $quantity,
            ];
        }

        $shippingAmount      = 0;
        $shippingPartnerId   = '';
        $shippingPartnerName = '';
        $freightBill         = '';
        $shopBaseUsStatus    = '';

        $shippingLines = data_get($orderDetail, 'shipping_lines', []);
        if ($shippingLines) {
            foreach ($shippingLines as $shippingLine) {
                $shippingAmount += data_get($shippingLine, 'price', 0);
            }
        }

        $fulfillments = data_get($orderDetail, 'fulfillments', []);
        if ($fulfillments) {
            foreach ($fulfillments as $fulfillment) {
                $trackingCompany   = data_get($fulfillment, 'tracking_company', '');
                $trackingNumber    = data_get($fulfillment, 'tracking_number', '');
                $fulfillmentStatus = data_get($fulfillment, 'status', '');
                if ($trackingCompany != '') {
                    $shippingPartnerId   = $trackingCompany;
                    $shippingPartnerName = $trackingCompany;
                }
                if ($trackingNumber != '') {
                    $freightBill = $trackingNumber;
                }
                if ($fulfillmentStatus != '') {
                    $shopBaseUsStatus = $fulfillmentStatus;
                }
            }
        }

        $financialStatus = data_get($orderDetail, 'financial_status', '');
        if ($financialStatus == 'refunded' || $financialStatus == 'partially_refunded') {
            $shopBaseUsStatus = ShopBaseUs::ORDER_STATUS_CANCELLED;
        }

        $status = $this->mapOrderStatus($shopBaseUsStatus);

        $totalAmount    = (float)data_get($orderDetail, 'subtotal_price', 0);
        $discountAmount = $orderAmount - $totalAmount;

        $usingCod = data_get($orderDetail, 'payment_gateway', '');
        if ($usingCod == '' || $usingCod == "cod") {
            $usingCod = true;
        } else {
            $usingCod = false;
        }

        $currencyId = null;
        $currencyData = data_get($orderDetail, 'currency', '');
        if ($currencyData == "USD") {
            $currency = Currency::where('code', 'US')->first();
            if ($currency) {
                $currencyId = $currency->id;
            }
        }

        // Make Order
        $dataResource = new Data3rdResource();

        $fullAddress = data_get($orderDetail, "billing_address.address1"). " " .
                       data_get($orderDetail, "billing_address.address2"). " " .
                       data_get($orderDetail, "billing_address.city"). " " .
                       data_get($orderDetail, "billing_address.country");
        $dataResource->receiver = [
            'name'    => data_get($orderDetail, "billing_address.name"),
            'phone'   => data_get($orderDetail, "billing_address.phone"),
            'address' => $fullAddress
        ];

        $orderCode = data_get($orderDetail, "order_number") . "_" . data_get($orderDetail, "id");

        $dataResource->marketplace_code     = Marketplace::CODE_SHOPBASE;
        $dataResource->id                   = data_get($orderDetail, "id");
        $dataResource->code                 = $orderCode;
        $dataResource->order_amount         = $orderAmount;
        $dataResource->shipping_amount      = $shippingAmount;
        $dataResource->discount_amount      = abs($discountAmount);
        $dataResource->total_amount         = $totalAmount;
        $dataResource->currency_id          = $currencyId;
        $dataResource->freight_bill         = $freightBill;
        $dataResource->intended_delivery_at = '';
        $dataResource->created_at_origin    = Carbon::createFromDate(data_get($orderDetail, 'created_at'))->toDateTimeString();
        $dataResource->using_cod            = $usingCod;
        $dataResource->status               = $status;
        $dataResource->items                = $itemSkus;
        $dataResource->shipping_partner     = [
            'id'   => $shippingPartnerId,
            'name' => $shippingPartnerName
        ];

        // dd($dataResource);

        $orderData = Service::order()->createOrderFrom3rdPartner($this->store, $dataResource);

        return $orderData;
    }

    /**
     * Map Order Status From ShopBaseUs
     *
     * @param string $shopBaseUsStatus
     * @return string
     */
    protected function mapOrderStatus(string $shopBaseUsStatus)
    {
        $listStatus = [
            ShopBaseUs::ORDER_STATUS_PENDING    => Order::STATUS_WAITING_CONFIRM,
            ShopBaseUs::ORDER_STATUS_OPEN       => Order::STATUS_WAITING_PROCESSING,
            ShopBaseUs::ORDER_STATUS_SUCCESS    => Order::STATUS_FINISH,
            ShopBaseUs::ORDER_STATUS_CANCELLED  => Order::STATUS_CANCELED,
            ShopBaseUs::ORDER_STATUS_ERROR      => Order::STATUS_WAITING_CONFIRM,
            ShopBaseUs::ORDER_STATUS_FAILURE    => Order::STATUS_WAITING_CONFIRM,
            ShopBaseUs::ORDER_STATUS_PROCESSING => Order::STATUS_WAITING_PROCESSING
        ];

        $status = Arr::get($listStatus, $shopBaseUsStatus, Order::STATUS_WAITING_INSPECTION);

        return $status;
    }
}
