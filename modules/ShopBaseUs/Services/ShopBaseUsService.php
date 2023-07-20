<?php

namespace Modules\ShopBaseUs\Services;

use Carbon\Carbon;
use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Modules\FreightBill\Models\FreightBill;
use Modules\Marketplace\Services\Marketplace;
use Modules\Marketplace\Services\MarketplaceException;
use Modules\Order\Models\Order;
use Modules\Product\Models\Product;
use Modules\ShopBase\Models\ShopBase;
use Modules\Store\Models\Store;
use Modules\ShopBaseUs\Commands\SyncShopBaseUsFreightBill;
use Modules\ShopBaseUs\Commands\SyncShopBaseUsOrder;
use Modules\ShopBaseUs\Commands\SyncShopBaseUsProduct;
use Modules\ShopBaseUs\Commands\SyncShopBaseUsProducts;

class ShopBaseUsService implements ShopBaseUsServiceInterface
{
    /**
     * @var ShopBaseUsApiInterface
     */
    protected $api;

    /**
     * ShopeeService constructor
     *
     * @param ShopBaseUsApiInterface $api
     */
    public function __construct(ShopBaseUsApiInterface $api)
    {
        $this->api = $api;
    }

    /**
     * Get shopee api
     *
     * @return ShopBaseUsApiInterface
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
        $accessToken = data_get($input,'access_token');
        $expiresIn   = (int) data_get($input,'access_token_expire_in');

        $refreshToken          = data_get($input,'refresh_token');
        $refreshTokenExpiresIn = (int) data_get($input,'refresh_token_expire_in');

        return [
            'access_token'            => $accessToken,
            'expire_at'               => time() + $expiresIn - (60*60),
            'refresh_token'           => $refreshToken,
            'refresh_token_expire_at' => time() + $refreshTokenExpiresIn - (60*60),
            'updated_at'              => Carbon::now()->toDateTimeString(),
        ];
    }

    /**
     * Connect Shopbase Store
     *
     * @param array $input
     * @return array
     */
    public function connect(array $input)
    {
        $shopName     = data_get($input, 'shop_name');
        $clientId     = data_get($input, 'client_id');
        $clientSecret = data_get($input, 'client_secret');
        $sharedSecret = data_get($input, 'shared_secret');
        $params = [
            'shop_name'     => $shopName,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
        ];
        $shopInfo = $this->api->getShopInfo($params)->getData('shop');

        if ($shopInfo) {
            $webhookData = $this->webhookRegister($shopName, $clientId, $clientSecret);
        } else {
            $webhookData = [];
        }
        // dd($shopInfo);

        $settings = [
            'shop_name'                        => $shopName,
            'client_id'                        => $clientId,
            'client_secret'                    => $clientSecret,
            'shared_secret'                    => $sharedSecret,
            'marketplace_store_id'             => data_get($shopInfo, 'id', 0),
            'SHOPBASE_PRODUCT_LAST_UPDATED_AT' => null,
            'shop_info'                        => $shopInfo,
            'webhooks'                         => $webhookData
        ];

        return $settings;
    }

    /**
     * Webhook Register For ShopBase Shop
     *
     * @param string $shopName
     * @param string $clientId
     * @param string $clientSecret
     * @return void
     */
    protected function webhookRegister($shopName, $clientId, $clientSecret)
    {
        // Kiểm tra xem shop đã đăng ký webhook chưa
        $store = Store::where([
            ['marketplace_code', Marketplace::CODE_SHOPBASE],
            ['settings->client_id', $clientId]
        ])->first();
        
        $params = [
            'shop_name'     => $shopName,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
        ];

        if (!$store || !$store->getSetting('webhooks')) {
            // Đăng ký Webhook sản phẩm
            $this->makeWebhookRegister('products/create', $params);
            // Đăng ký Webhook đơn hàng
            $this->makeWebhookRegister('orders/create', $params);
            $this->makeWebhookRegister('orders/updated', $params);
            $this->makeWebhookRegister('orders/cancelled', $params);
            // Đăng ký Webhook Fulfillment
            $this->makeWebhookRegister('fulfillments/create', $params);
            $this->makeWebhookRegister('fulfillments/update', $params);
        }
        // Lấy danh sách Webhook 
        $response = $this->api->getWebhookList($params)->getData('webhooks');
        return $response;
    }

    /**
     * Make Webhook Register
     *
     * @param string $topic
     * @param array $params
     * @return void
     */
    protected function makeWebhookRegister(string $topic, array $params)
    {
        if (App::environment() == 'local') {
            $addressWebhook = 'https://q4ax1ebxux5kjgvt2dkd5x.hooks.webhookrelay.com';
        } else {
            $addressWebhook = request()->getSchemeAndHttpHost() . '/webhook/shopbase';
        }

        $paramRequest = [
            'shop_name'     => data_get($params, 'shop_name', ''),
            'client_id'     => data_get($params, 'client_id', ''),
            'client_secret' => data_get($params, 'client_secret', ''),
        ];

        $fields = '"id","updated_at"';
        
        if ($topic == 'fulfillments/create' || $topic == 'fulfillments/update') {
            $fields = '"id","order_id","updated_at"';
        }

        $bodyWebhookOrder = '{
            "webhook": {
              "address": "' . $addressWebhook . '",
              "fields": [
                ' . $fields . '
              ],
              "format": "json",
              "metafield_namespaces": [],
              "topic": "' . $topic . '"
            }
        }';

        $paramRequest['body'] = json_decode($bodyWebhookOrder, true);
        $this->api->createWebhook($paramRequest); 
    }

    /**
     * Đồng bộ đơn ShopBaseUs theo danh sách order code
     *
     * @param Store $store
     * @return Order[]|null
     * @throws RestApiException
     * @throws WorkflowException
     */
    public function syncOrders(Store $store)
    {
        // return (new SyncShopBaseUsOrders($store))->handle();
    }

    /**
     * Thực hiện đồng bộ đơn
     *
     * @param Store $store
     * @param array $orderInput Thông tin order theo response của ShopBaseUs webhook
     * @return Order
     * @throws ValidationException
     */
    public function syncOrder(Store $store, array $orderInput)
    {
        return (new SyncShopBaseUsOrder($store, $orderInput))->handle();
    }

    /**
     * Đồng bộ toàn bộ sản phẩm từ ShopBaseUs theo merchant
     * @param Store $store
     * @param int $merchantId
     * @param bool $filterUpdateTime
     * @return array|null
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function syncProducts(Store $store, $filterUpdateTime = true)
    {
        return (new SyncShopBaseUsProducts($store, null, $filterUpdateTime))->handle();
    }

    /**
     * Đồng bộ sản phẩm từ ShopBaseUs
     * @param Store $store
     * @param array $shopBaseUsItemId
     * @return array|Product[]
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function syncProduct(Store $store, $shopBaseUsItemId)
    {
        return (new SyncShopBaseUsProduct($store, $shopBaseUsItemId))->handle();
    }

    /**
     * Đồng bộ mã vận đơn ShopBaseUs
     *
     * @param Store $store
     * @param Order $order
     * @param string $trackingNo
     * @return FreightBill[]
     * @throws RestApiException
     */
    public function syncFreightBill(Store $store, Order $order, string $trackingNo)
    {
        return (new SyncShopBaseUsFreightBill($store, $order, $trackingNo))->handle();
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
            ShopBaseUs::LOGISTICS_ATTEMPTED_DELIVERY => FreightBill::STATUS_FAILED_DELIVERY,
            ShopBaseUs::LOGISTICS_READY_TO_PICKUP    => FreightBill::STATUS_WAIT_FOR_PICK_UP,
            ShopBaseUs::LOGISTICS_CONFIRMED          => FreightBill::STATUS_WAIT_FOR_PICK_UP,
            ShopBaseUs::LOGISTICS_IN_TRANSIT         => FreightBill::STATUS_DELIVERING,
            ShopBaseUs::LOGISTICS_OUT_FOR_DELIVERY   => FreightBill::STATUS_DELIVERING,
            ShopBaseUs::LOGISTICS_DELIVERED          => FreightBill::STATUS_DELIVERED,
            ShopBaseUs::LOGISTICS_DELAYED            => FreightBill::STATUS_DELIVERING,
            ShopBaseUs::LOGISTICS_FAILURE            => FreightBill::STATUS_FAILED_DELIVERY,
            ShopBaseUs::LOGISTICS_NOT_FOUND          => FreightBill::STATUS_FAILED_DELIVERY,
            ShopBaseUs::LOGISTICS_INVALID            => FreightBill::STATUS_FAILED_DELIVERY,
        ], $logisticsStatus, null);
    }
}
