<?php

namespace Modules\TikTokShop\Commands;

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
use Modules\TikTokShop\Services\TikTokShop;
use Modules\Product\Models\Sku;
use Modules\Store\Models\Store;
use Modules\Store\Models\StoreSku;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;

class SyncTikTokShopOrder extends CommandBus
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
     * SyncTikTokShopOrderJob constructor
     *
     * @param Store $store
     * @param array $input Thông tin order theo response của TikTokShop api /orders/detail
     * @param User $creator
     */
    public function __construct(Store $store, array $input)
    {
        $this->store   = $store;
        $this->input   = $input;
        $this->creator = Service::user()->getSystemUserDefault();
        $this->api     = Service::TikTokShop()->api();

        $this->logger = LogService::logger('tiktokshop', [
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
        // Get Data Order Detail From TikTokShop
        $orderId = data_get($this->input, 'order_id');

        $paramsRequest = [
            'shop_id'      => $this->store->marketplace_store_id,
            'order_id'     => $orderId,
            'access_token' => $this->store->getSetting('access_token')
        ];

        $orderDetail = $this->api->getOrderDetails($paramsRequest)->getData('data.order_list');
        $orderDetail = $orderDetail[0];
        $this->logger->info('order-raw-data', [$orderDetail]);
        // dd($orderDetail);
        $orderAmount = 0;

        $tikTokShopStatus = data_get($orderDetail, 'order_status', '');

        $status = $this->mapOrderStatus($tikTokShopStatus);

        $orderAmount = 0;

        $itemSkus = [];

        $items = data_get($orderDetail, "item_list", []);

        // dd($items);

        foreach ($items as $item) {

            $price    = data_get($item, 'sku_original_price');
            $quantity = data_get($item, 'quantity');
            $totalAmount = (float) $price * (int) $quantity;
            $orderAmount += $totalAmount;

            $discountAmount = (float) data_get($item, 'sku_platform_discount_total');

            // Check Sku Đã tồn tại trên hệ thống chưa

            $productId = data_get($item, 'product_id');
            $skuId     = data_get($item, 'sku_id');
            $sellerSku = data_get($item, 'seller_sku');
            if ($sellerSku) {
                $skuCode = $sellerSku;
            } else {
                $skuCode = $productId . "_" . $skuId;   
            }
            $storeSku = Service::store()->getStoreSkuOnSell($this->store, $skuId, $skuCode);

            if (!$storeSku) {
                // Tạo mới product
                (new SyncTikTokShopProduct($this->store, $productId))->handle();
            }

            $itemSkus[] = [
                'id_origin'       => $skuId,
                'code'            => $skuCode,
                'discount_amount' => $discountAmount,
                'price'           => $price,
                'quantity'        => $quantity,
            ];
        }

        $shippingAmount = (float)data_get($orderDetail, "payment_info.shipping_fee", 0);
        $totalAmount    = (float)data_get($orderDetail, 'payment_info.sub_total', 0);
        $discountAmount = $orderAmount - $totalAmount;

        $usingCod = data_get($orderDetail, 'payment_method', '');
        if ($usingCod == '' || $usingCod == "CASH_ON_DELIVERY") {
            $usingCod = true;
        } else {
            $usingCod = false;
        }

        $currencyId   = null;
        $currencyCode = '';
        $currencyData = trim(data_get($orderDetail, 'payment_info.currency', ''));

        switch ($currencyData) {
            case 'IDR':
                $currencyCode = 'IND';
                break;
            case 'PHP':
                $currencyCode = 'PHI';
                break;
            case 'VND':
                $currencyCode = 'VIE';
                break;
            case 'THB':
                $currencyCode = 'THA';
                break;
            
            default:
                $currencyCode = '';
                break;
        }

        if ($currencyCode) {
            $currency = Currency::where('code', $currencyCode)->first();
            if ($currency) {
                $currencyId = $currency->id;
            }
        }

        // Make Order
        $dataResource = new Data3rdResource();

        $dataResource->receiver = [
            'name'    => data_get($orderDetail, "recipient_address.name"),
            'phone'   => data_get($orderDetail, "recipient_address.phone"),
            'address' => data_get($orderDetail, "recipient_address.full_address")
        ];
        $dataResource->marketplace_code     = Marketplace::CODE_TIKTOKSHOP;
        $dataResource->id                   = data_get($orderDetail, "order_id");
        $dataResource->code                 = data_get($orderDetail, "order_id");
        $dataResource->order_amount         = $orderAmount;
        $dataResource->shipping_amount      = $shippingAmount;
        $dataResource->discount_amount      = abs($discountAmount);
        $dataResource->total_amount         = $totalAmount;
        $dataResource->currency_id          = $currencyId;
        $dataResource->freight_bill         = data_get($orderDetail, "tracking_number");
        $dataResource->intended_delivery_at = '';
        $dataResource->created_at_origin    = Carbon::createFromTimestamp(data_get($orderDetail, 'create_time'))->toDateTimeString();
        $dataResource->using_cod            = $usingCod;
        $dataResource->status               = $status;
        $dataResource->items                = $itemSkus;
        $dataResource->shipping_partner     = [
            'id'   => data_get($orderDetail, "shipping_provider_id"),
            'name' => data_get($orderDetail, "shipping_provider"),
        ];

        // dd($dataResource);

        $orderData = Service::order()->createOrderFrom3rdPartner($this->store, $dataResource);

        return $orderData;
    }

    /**
     * Map Order Status From TikTokShop
     *
     * @param string $tikTokShopStatus
     * @return string
     */
    protected function mapOrderStatus(string $tikTokShopStatus)
    {
        $listStatus = [
            TikTokShop::ORDER_STATUS_UNPAID              => Order::STATUS_WAITING_INSPECTION,
            TikTokShop::ORDER_STATUS_AWAITING_SHIPMENT   => Order::STATUS_WAITING_DELIVERY,
            TikTokShop::ORDER_STATUS_AWAITING_COLLECTION => Order::STATUS_WAITING_DELIVERY,
            TikTokShop::ORDER_STATUS_PARTIALLY_SHIPPING  => Order::STATUS_DELIVERING,
            TikTokShop::ORDER_STATUS_IN_TRANSIT          => Order::STATUS_DELIVERING,
            TikTokShop::ORDER_STATUS_DELIVERED           => Order::STATUS_DELIVERED,
            TikTokShop::ORDER_STATUS_COMPLETED           => Order::STATUS_FINISH,
            TikTokShop::ORDER_STATUS_CANCELLED           => Order::STATUS_CANCELED,
        ];

        $status = Arr::get($listStatus, $tikTokShopStatus, Order::STATUS_WAITING_INSPECTION);

        return $status;
    }
}
