<?php

namespace Modules\Tiki\Services;

use Carbon\Carbon;
use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modules\FreightBill\Models\FreightBill;
use Modules\Tiki\Commands\SyncTikiProduct;
use Modules\Tiki\Commands\SyncTikiProducts;
use Modules\Marketplace\Services\MarketplaceException;
use Modules\Order\Models\Order;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Store\Models\Store;
use Modules\Tiki\Commands\SyncQueueSubscription;
use Modules\Tiki\Commands\SyncTikiOrder;
use Modules\Tiki\Commands\SyncTikiOrders;
use Modules\User\Models\User;

class TikiService implements TikiServiceInterface
{
    /**
     * @var TikiApiInterface
     */
    protected $api;

    /**
     * ShopeeService constructor
     *
     * @param TikiApiInterface $api
     */
    public function __construct(TikiApiInterface $api)
    {
        $this->api = $api;
    }

    /**
     * Get shopee api
     *
     * @return TikiApiInterface
     */
    public function api()
    {
        return $this->api;
    }

    /**
     * Tạo thông tin token kết nối
     *
     * @param array $input
     * @return array
     */
    public function makeToken(array $input)
    {
        $accessToken  = data_get($input,'access_token');
        $refreshToken = data_get($input,'refresh_token');
        $expiresIn    = (int) data_get($input,'expires_in');

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'expire_at'     => time() + $expiresIn - (60*60),
            'updated_at'    => Carbon::now()->toDateTimeString(),
        ];
    }

    /**
     * Đồng bộ đơn Tiki theo danh sách order code
     *
     * @param Store $store
     * @return Order[]|null
     * @throws RestApiException
     * @throws WorkflowException
     */
    public function syncOrders(Store $store)
    {
        return (new SyncTikiOrders($store))->handle();
    }

    /**
     * Thực hiện đồng bộ đơn
     *
     * @param Store $store
     * @param array $orderInput Thông tin order theo response của Tiki webhook
     * @return Order
     * @throws ValidationException
     */
    public function syncOrder(Store $store, array $orderInput)
    {
        return (new SyncTikiOrder($store, $orderInput))->handle();
    }

    /**
     * Đồng bộ toàn bộ sản phẩm từ Tiki theo merchant
     * @param Store $store
     * @param int $merchantId
     * @param bool $filterUpdateTime
     * @return array|null
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function syncProducts(Store $store, $filterUpdateTime = true)
    {
        return (new SyncTikiProducts($store, null, $filterUpdateTime))->handle();
    }

    /**
     * Đồng bộ sản phẩm từ Tiki
     * @param Store $store
     * @param array $TikiItemId
     * @return array|Product[]
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function syncProduct(Store $store, $TikiItemId)
    {
        return (new SyncTikiProduct($store, $TikiItemId))->handle();
    }

    /**
     * Get queue sub từ Tiki
     * @param Store $store
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function syncQueueSubscription(Store $store)
    {
        return (new SyncQueueSubscription($store))->handle();
    }

    /**
     * @param Store $store
     * @return Store $store
     * @throws RestApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function refreshToken(Store $store)
    {
        $params = [
            'refresh_token' => $store->getSetting('refresh_token')
        ];

        $token        = $this->api->refreshToken($params)->getData();
        $settingToken = $this->makeToken($token);

        if ($settingToken) {
            $settingToken = array_merge($settingToken, $store->settings);
            $store->settings = $settingToken;
            $store->save();
        }

        return $store;
    }
}
