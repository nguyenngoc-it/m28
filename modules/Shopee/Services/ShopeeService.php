<?php

namespace Modules\Shopee\Services;

use Carbon\Carbon;
use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modules\FreightBill\Models\FreightBill;
use Modules\Marketplace\Services\MarketplaceException;
use Modules\Order\Models\Order;
use Modules\Product\Models\Sku;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Shopee\Commands\FindSkusByVariations;
use Modules\Shopee\Commands\ShopeeDownloadShippingDocument;
use Modules\Shopee\Commands\SyncShopeeFreightBill;
use Modules\Shopee\Commands\SyncShopeeOrder;
use Modules\Shopee\Commands\SyncShopeeOrders;
use Modules\Shopee\Commands\SyncShopeeProduct;
use Modules\Shopee\Commands\SyncShopeeProducts;
use Modules\Stock\Models\Stock;
use Modules\Store\Models\Store;
use Modules\User\Models\User;

class ShopeeService implements ShopeeServiceInterface
{
    /**
     * @var ShopeeApiInterface
     */
    protected $api;

    /**
     * @var ShopeePublicApiInterface
     */
    protected $publicApi;

    /**
     * ShopeeService constructor
     *
     * @param ShopeeApiInterface $api
     */
    public function __construct(ShopeeApiInterface $api)
    {
        $this->api       = $api;
        $this->publicApi = new ShopeePublicApi();
    }

    /**
     * Get shopee api
     *
     * @return ShopeeApiInterface
     */
    public function api()
    {
        return $this->api;
    }

    /**
     * Get shopee public api
     *
     * @return ShopeePublicApiInterface
     */
    public function publicApi()
    {
        return $this->publicApi;
    }

    /**
     * Make store connector
     *
     * @param Store $store
     * @return ShopeeStoreConnector
     */
    public function storeConnector(Store $store)
    {
        return new ShopeeStoreConnector($store);
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
            return Shopee::ERROR_ACCESS_TOKEN_INVALID;
        }

        if ($error === 'error_auth' && Str::contains($message, 'Invalid refresh_token')) {
            return Shopee::ERROR_REFRESH_TOKEN_INVALID;
        }

        if ($error === 'error_auth' && Str::contains($message, 'Partner and shop has no linked')) {
            return Shopee::ERROR_NO_LINKED;
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
        return [
            'refresh_token' => $input['refresh_token'],
            'access_token' => $input['access_token'],
            'expire_in' => $input['expire_in'],
            'refresh_token_expired_at' => time() + (30 * 24 * 60 * 60) - (60 * 60), // buffer 1h phòng sai lệch time
            'access_token_expired_at' => time() + $input['expire_in'] - (60 * 60), // buffer 1h phòng sai lệch time
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
            Shopee::LOGISTICS_PICKUP_DONE => FreightBill::STATUS_CONFIRMED_PICKED_UP,
            Shopee::LOGISTICS_PICKUP_FAILED => FreightBill::STATUS_FAILED_PICK_UP,
            Shopee::LOGISTICS_DELIVERY_DONE => FreightBill::STATUS_DELIVERED,
            Shopee::LOGISTICS_REQUEST_CANCELED => FreightBill::STATUS_CANCELLED,
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
     * @param array $input Thông tin order theo response của shopee api /orders/detail
     * @param User $creator
     * @return Order
     */
    public function syncOrder(Store $store, array $input, User $creator)
    {
        return (new SyncShopeeOrder($store, $input, $creator))->dispatch();
    }

    /**
     * Đồng bộ toàn bộ sản phẩm từ shopee theo merchant
     * @param int $storeId
     * @param int $merchantId
     * @param bool $filterUpdateTime
     * @return array|null
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function syncProducts($storeId, $merchantId, $filterUpdateTime = true)
    {
        return (new SyncShopeeProducts($storeId, $merchantId, $filterUpdateTime))->handle();
    }

    /**
     * Đồng bộ sản phẩm từ shopee
     * @param int $storeId
     * @param array $shopeeItemIds
     * @return void
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function syncProduct($storeId, $shopeeItemIds = [])
    {
        (new SyncShopeeProduct($storeId, $shopeeItemIds))->handle();
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
     * Đồng bộ mã vận đơn shopee
     * @param int $shopId
     * @param string $orderCode
     * @param string $trackingNo
     * @return array|FreightBill[]
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function syncFreightBill($shopId, $orderCode, $trackingNo)
    {
        return (new SyncShopeeFreightBill($shopId, $orderCode, $trackingNo))->handle();
    }

    /**
     * Tìm skus tương ứng từ variations bên shopee
     *
     * @param Store $store
     * @param array $models
     * @return Sku[]
     */
    public function findSkusByVariations(Store $store, array $models)
    {
        return (new FindSkusByVariations($store, $models))->handle();
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
        $code = 'SHOPEE_' . $logistic['logistics_channel_id'];
        return ShippingPartner::query()->firstOrCreate([
            'tenant_id' => $tenantId,
            'provider' => ShippingPartner::PROVIDER_SHOPEE,
            'code' => $code,
        ], [
            'name' => $logistic['logistics_channel_name'],
            'settings' => Arr::only($logistic, ['logistics_channel_id']),
            'alias' => [$code, strtolower($code)],
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

        $order->freightBills()
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
     * @return array|mixed
     * @throws MarketplaceException
     * @throws RestApiException
     */
    public function getWarehouseDetail(Store $store)
    {
        return $store->shopeeApi()->getWarehouseDetail()
            ->getData();
    }

    /**
     * @param Store $store
     * @param Sku $sku
     * @param integer $quantity
     * @param array $locationIds
     * @param array $params
     * @return array|mixed
     * @throws MarketplaceException
     * @throws RestApiException
     */
    public function updateStock(Store $store, Sku $sku, $quantity, $locationIds = [], &$params = [])
    {
        $sellerStock = [];
        $stockList   = [];
        if(!empty($locationIds)) { //nếu shop có kho
            foreach ($locationIds as $locationId) {
                $sellerStock[] = ['stock' => $quantity, 'location_id' => $locationId];
            }
        } else {
            $sellerStock[] = ['stock' => $quantity];
        }

        $stockList[] = [
            'model_id' => $sku->sku_id_origin,
            'seller_stock' => $sellerStock
        ];

        $params = [
            'item_id' => $sku->product->product_id_origin,
            'stock_list' => $stockList
        ];

        return $store->shopeeApi()->updateStock($params)
            ->getData();
    }

    /**
     * @param $shippingCarrierName
     * @return array
     */
    public function makeLogisticDataFromName($shippingCarrierName)
    {
        $logisticsChannelId = trim(str_replace(' ', '_', $shippingCarrierName));
        return [
            'logistics_channel_id' => $logisticsChannelId,
            'logistics_channel_name' => $shippingCarrierName,
        ];
    }
}
