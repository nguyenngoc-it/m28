<?php

namespace Modules\Sapo\Commands;

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
use Modules\Sapo\Services\Sapo;
use Modules\Product\Models\Sku;
use Modules\Store\Models\Store;
use Modules\Store\Models\StoreSku;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;

class SyncSapoOrder extends CommandBus
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
     * SyncSapoOrderJob constructor
     *
     * @param Store $store
     * @param array $input Thông tin order theo response của Sapo api /orders/detail
     * @param User $creator
     */
    public function __construct(Store $store, array $input)
    {
        $this->store   = $store;
        $this->input   = $input;
        $this->creator = Service::user()->getSystemUserDefault();
        $this->api     = Service::Sapo()->api();

        $this->logger = LogService::logger('sapo', [
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
        // Get Data Order Detail From Sapo
        $orderId = data_get($this->input, 'order_id');

        $paramsRequest = [
            'order_id'      => $orderId,
            'shop_name'     => $this->store->getSetting('shop_name'),
            'client_id'     => $this->store->getSetting('client_id'),
            'client_secret' => $this->store->getSetting('client_secret'),
        ];

        $orderDetail = $this->api->getOrderDetails($paramsRequest)->getData('order');
        
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

            $discountAmount = (float) data_get($item, 'total_discount');

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
                (new SyncSapoProduct($this->store, $productId))->handle();
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
        $sapoStatus    = '';

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
                    $sapoStatus = $fulfillmentStatus;
                }
            }
        }

        $financialStatus = data_get($orderDetail, 'financial_status', '');
        $orderStatus     = data_get($orderDetail, 'status', '');
        if ($financialStatus == 'refunded' || $financialStatus == 'partially_refunded' || $orderStatus == 'cancelled') {
            $sapoStatus = Sapo::ORDER_STATUS_CANCELLED;
        }

        $status = $this->mapOrderStatus($sapoStatus);

        $totalAmount    = (double) data_get($orderDetail, 'sub_total_price', 0);
        $discountAmount = (double) data_get($orderDetail, 'total_discounts', 0);

        $usingCod = data_get($orderDetail, 'financial_status', '');
        if ($usingCod != "paid") {
            $usingCod = true;
        } else {
            $usingCod = false;
        }

        $currencyId = null;
        $currencyData = data_get($orderDetail, 'currency', '');
        if ($currencyData == "USD") {
            $currencyCode = 'US';
        } else {
            $currencyCode = 'VIE';
        }

        $currency = Currency::where('code', $currencyCode)->first();
        if ($currency) {
            $currencyId = $currency->id;
        }

        // Make Order
        $dataResource = new Data3rdResource();

        $fullAddress = data_get($orderDetail, "billing_address.address1"). " " .
                       data_get($orderDetail, "billing_address.address2"). " " .
                       data_get($orderDetail, "billing_address.ward"). " " .
                       data_get($orderDetail, "billing_address.district"). " " .
                       data_get($orderDetail, "billing_address.city"). " " .
                       data_get($orderDetail, "billing_address.country");
        $dataResource->receiver = [
            'name'    => data_get($orderDetail, "billing_address.name"),
            'phone'   => data_get($orderDetail, "billing_address.phone"),
            'address' => $fullAddress
        ];

        $orderCode = data_get($orderDetail, "order_number") . "_" . data_get($orderDetail, "id");

        $dataResource->marketplace_code     = Marketplace::CODE_SAPO;
        $dataResource->id                   = data_get($orderDetail, "id");
        $dataResource->code                 = $orderCode;
        $dataResource->order_amount         = $orderAmount;
        $dataResource->shipping_amount      = $shippingAmount;
        $dataResource->discount_amount      = abs($discountAmount);
        $dataResource->total_amount         = $totalAmount;
        $dataResource->currency_id          = $currencyId;
        $dataResource->freight_bill         = $freightBill;
        $dataResource->intended_delivery_at = '';
        $dataResource->created_at_origin    = Carbon::createFromDate(data_get($orderDetail, 'created_on'))->toDateTimeString();
        $dataResource->using_cod            = $usingCod;
        $dataResource->status               = $status;
        $dataResource->description          = data_get($orderDetail, "note");
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
     * Map Order Status From Sapo
     *
     * @param string $sapoStatus
     * @return string
     */
    protected function mapOrderStatus(string $sapoStatus)
    {
        $listStatus = [
            Sapo::ORDER_STATUS_PENDING    => Order::STATUS_WAITING_CONFIRM,
            Sapo::ORDER_STATUS_OPEN       => Order::STATUS_WAITING_PROCESSING,
            Sapo::ORDER_STATUS_SUCCESS    => Order::STATUS_FINISH,
            Sapo::ORDER_STATUS_CANCELLED  => Order::STATUS_CANCELED,
            Sapo::ORDER_STATUS_ERROR      => Order::STATUS_WAITING_CONFIRM,
            Sapo::ORDER_STATUS_FAILURE    => Order::STATUS_WAITING_CONFIRM,
            Sapo::ORDER_STATUS_PROCESSING => Order::STATUS_WAITING_PROCESSING
        ];

        $status = Arr::get($listStatus, $sapoStatus, Order::STATUS_WAITING_INSPECTION);

        return $status;
    }
}
