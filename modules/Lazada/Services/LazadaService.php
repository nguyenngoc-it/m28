<?php

namespace Modules\Lazada\Services;

use Carbon\Carbon;
use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modules\FreightBill\Models\FreightBill;
use Modules\Lazada\Commands\SyncLazadaFreightBill;
use Modules\Lazada\Commands\SyncLazadaOrder;
use Modules\Lazada\Commands\SyncLazadaProduct;
use Modules\Lazada\Commands\SyncLazadaProducts;
use Modules\Lazada\Commands\SyncStockSku;
use Modules\Lazada\Commands\SyncStockSkus;
use Modules\Marketplace\Services\MarketplaceException;
use Modules\Order\Models\Order;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Shopee\Commands\ShopeeDownloadShippingDocument;
use Modules\Shopee\Commands\SyncShopeeFreightBill;
use Modules\Shopee\Commands\SyncShopeeOrder;
use Modules\Shopee\Commands\SyncShopeeOrders;
use Modules\Shopee\Commands\SyncShopeeProduct;
use Modules\Shopee\Commands\SyncShopeeProducts;
use Modules\Store\Models\Store;
use Modules\User\Models\User;

class LazadaService implements LazadaServiceInterface
{
    /**
     * @var LazadaApiInterface
     */
    protected $api;

    /**
     * ShopeeService constructor
     *
     * @param LazadaApiInterface $api
     */
    public function __construct(LazadaApiInterface $api)
    {
        $this->api = $api;
    }

    /**
     * Get shopee api
     *
     * @return LazadaApiInterface
     */
    public function api()
    {
        return $this->api;
    }

    /**
     * Make store connector
     *
     * @param Store $store
     * @return LazadaStoreConnector
     */
    public function storeConnector(Store $store)
    {
        return new LazadaStoreConnector($store);
    }

    /**
     * Detect api response error
     *
     * @param string $error
     * @param string $message
     * @return string|null
     */
    public function detectApiError($error, $message)
    {
        if ($error === 'error_auth' && Str::contains($message, 'Invalid access_token')) {
            return Lazada::ERROR_ACCESS_TOKEN_INVALID;
        }

        if ($error === 'error_auth' && Str::contains($message, 'Invalid refresh_token')) {
            return Lazada::ERROR_REFRESH_TOKEN_INVALID;
        }

        return null;
    }

    /**
     * Tạo thông tin token kết nối
     *
     * @param array $input
     * @return array
     */
    public function makeToken(array $input)
    {
        $accessToken = data_get($input, 'access_token');
        $refreshToken = data_get($input, 'refresh_token');
        $expiresIn = (int)data_get($input, 'expires_in');
        $refreshExpiresIn = data_get($input, 'refresh_expires_in');
        $account = data_get($input, 'account');
        $accountId = data_get($input, 'account_id');
        $countryUserInfo = data_get($input, 'country_user_info');

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expire_at' => time() + $expiresIn - (60 * 60),
            'refresh_expires_in' => $refreshExpiresIn,
            'account' => $account,
            'account_id' => $accountId,
            'country_user_info' => $countryUserInfo,
            'updated_at' => Carbon::now()->toDateTimeString(),
        ];
    }

    /**
     * Mapping trạng thái vận chuyển
     *
     * @param string $logisticsStatus
     * @return string|null
     */
    public function mapFreightBillStatus($logisticsStatus)
    {
        return Arr::get([
            Lazada::LOGISTICS_PICKUP_DONE => FreightBill::STATUS_CONFIRMED_PICKED_UP,
            Lazada::LOGISTICS_PICKUP_FAILED => FreightBill::STATUS_FAILED_PICK_UP,
            Lazada::LOGISTICS_DELIVERY_DONE => FreightBill::STATUS_DELIVERED,
            Lazada::LOGISTICS_REQUEST_CANCELED => FreightBill::STATUS_CANCELLED,
        ], $logisticsStatus);
    }

    /**
     * Đồng bộ đơn shopee theo danh sách order code
     * @param int $shopId
     * @param array $orderInputs = [['order_sn' => 'xxx', 'order_status' => 'xxx'], ...]
     * @return Order[]|null
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function syncOrders($shopId, array $orderInputs)
    {
        return (new SyncShopeeOrders($shopId, $orderInputs))->handle();
    }

    /**
     * Thực hiện đồng bộ đơn
     *
     * @param Store $store
     * @param array $orderInput Thông tin order theo response của Lazada webhook
     * @return Order
     * @throws ValidationException
     */
    public function syncOrder(Store $store, array $orderInput)
    {
        return (new SyncLazadaOrder($store, $orderInput))->handle();
    }

    /**
     * Đồng bộ toàn bộ sản phẩm từ lazada theo merchant
     * @param Store $store
     * @param int $merchantId
     * @param bool $filterUpdateTime
     * @return array|null
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function syncProducts(Store $store, $filterUpdateTime = true)
    {
        return (new SyncLazadaProducts($store, null, $filterUpdateTime))->handle();
    }

    /**
     * Đồng bộ sản phẩm từ shopee
     * @param int $storeId
     * @param array $lazadaItemId
     * @return array|Product[]
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function syncProduct(Store $store, $lazadaItemId)
    {
        return (new SyncLazadaProduct($store, $lazadaItemId))->handle();
    }

    /**
     * lấy link intem của shopee
     * @param int $shippingPartnerId
     * @param array $freightBillCodes
     * @return array|string
     */
    public function downloadShippingDocument($shippingPartnerId, $freightBillCodes)
    {
        return (new ShopeeDownloadShippingDocument($shippingPartnerId, $freightBillCodes))->handle();
    }

    /**
     * Đồng bộ mã vận đơn KiotViet
     *
     * @param Store $store
     * @param Order $order
     * @param string $trackingNo
     * @return FreightBill[]
     * @throws RestApiException
     */
    public function syncFreightBill(Store $store, Order $order, string $trackingNo)
    {
        return (new SyncLazadaFreightBill($store, $order, $trackingNo))->handle();
    }

    /**
     * Tìm skus tương ứng từ variations bên kiotviet
     *
     * @param Store $store
     * @param array $variations
     * @return Sku[]
     */
    public function findSkusByVariations(Store $store, array $variations)
    {
        $variationIds = [];
        foreach ($variations as $variation) {
            $variationId = $variation['sku_id'];
            $variationIds[] = $variationId;
        }

        // Case sync sku tự động từ kiotviet
        $skus = Sku::query()
            ->where('merchant_id', $store->merchant_id)
            ->whereIn('sku_id_origin', $variationIds)
            ->get();

        // Case map sku thủ công
        $storeSkus = $store->storeSkus()
            ->whereIn('code', $variationIds)
            ->with('sku')
            ->get();

        $results = [];
        foreach ($variations as $variation) {
            $variationId = $variation['sku_id'];
            $sku = $skus->firstWhere('sku_id_origin', $variationId);

            if ($sku instanceof Sku) {
                $results[$variationId] = $sku;
                continue;
            } else {
                // Tạo mới product
                $productId = $variation['product_id'];
                $dataProduct = (new SyncLazadaProduct($store, $productId))->handle();
                if ($dataProduct instanceof Product) {
                    $results[$variationId] = $dataProduct->skus->first();
                    continue;
                }

            }

            if ($storeSku = $storeSkus->firstWhere('code', $variationId)) {
                $results[$variationId] = $storeSku->sku;
            }
        }

        return $results;
    }

    /**
     * Lưu thông tin đối tác vận chuyển shopee
     *
     * @param int $tenantId
     * @param array $logistic
     * @return ShippingPartner|object
     */
    public function makeShippingPartner($tenantId, $logistic)
    {
        return ShippingPartner::query()->firstOrCreate([
            'tenant_id' => $tenantId,
            'provider' => ShippingPartner::PROVIDER_LAZADA,
            'code' => 'LAZADA_' . $logistic['logistic_id'],
        ], [
            'name' => $logistic['logistic_name'],
            'settings' => Arr::only($logistic, ['logistic_id']),
        ]);
    }

    /**
     * Cập nhật đối tác vận chuyển của shopee cho order
     *
     * @param Order $order
     * @param ShippingPartner $shippingPartner
     * @param User $creator
     * @return bool
     */
    public function updateOrderShippingPartner(Order $order, ShippingPartner $shippingPartner, User $creator)
    {
        if ($order->shipping_partner_id === $shippingPartner->id) {
            return true;
        }

        $fromShippingPartner = $order->shippingPartner;

        $order->update(['shipping_partner_id' => $shippingPartner->id]);

        $order->orderPackings()
            ->where('shipping_partner_id', '!=', $shippingPartner->id)
            ->update(['shipping_partner_id' => $shippingPartner->id]);

        $order->activityLogger($creator)->changeShippingPartner($fromShippingPartner, $shippingPartner);

        return true;
    }

    /**
     * Find shopee order
     *
     * @param int $storeId
     * @param string $code
     * @return Order|object|null
     */
    public function findOrder($storeId, $code)
    {
        return Order::query()
            ->where('store_id', $storeId)
            ->where('code', $code)
            ->first();
    }

    /**
     * @param Store $store
     * @return array|mixed
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function getLogistic(Store $store)
    {
        return $store->shopeeApi()->getLogistics()
            ->getData('response.logistics_channel_list');
    }

    /**
     * @param Store $store
     * @param array $orderCodes
     * @return array|mixed
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function getOrderDetails(Store $store, $orderCodes)
    {
        return $store->shopeeApi()->getOrderDetails([
            'order_sn_list' => implode(',', $orderCodes),
            'response_optional_fields' => 'buyer_user_id,buyer_username,estimated_shipping_fee,recipient_address,actual_shipping_fee ,goods_to_declare,note,note_update_time,item_list,pay_time,dropshipper,dropshipper_phone,split_up,buyer_cancel_reason,cancel_by,cancel_reason,actual_shipping_fee_confirmed,buyer_cpf_id,fulfillment_flag,pickup_done_time,package_list,shipping_carrier,payment_method,total_amount,buyer_username,invoice_data,checkout_shipping_carrier,reverse_shipping_fee,order_chargeable_weight_gram',
        ])->getData('response.order_list');
    }

    /**
     * @param Store $store
     * @param $merchantId
     * @param $filterUpdateTime
     * @param $type
     * @return mixed|null
     */
    public function syncStockSkus(Store $store, $merchantId, $filterUpdateTime = true, $type)
    {
        return (new SyncStockSkus($store, $merchantId, $filterUpdateTime, $type))->handle();
    }

    /**
     * @param Sku $sku
     * @param Store $store
     * @return mixed|void
     */
    public function syncStockSku(Sku $sku,Store $store)
    {
         (new SyncStockSku($sku, $store))->handle();
    }
}
