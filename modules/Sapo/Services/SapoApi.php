<?php

namespace Modules\Sapo\Services;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Modules\Marketplace\Services\Marketplace;
use Psr\Log\LoggerInterface;
use Throwable;

class SapoApi implements SapoApiInterface
{
    /**
     * @var
     */
    protected $apiUrl = 'mysapo.net';

    /**
     * @var int
     */
    protected $partnerId;

    /**
     * @var string
     */
    protected $partnerKey;

    /**
     * @var Client
     */
    protected $http;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * ShopeeApi constructor
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->apiUrl     = config('services.sapo.api_url');
        $this->partnerId  = config('services.sapo.client_id');
        $this->partnerKey = config('services.sapo.client_secret');
        $this->http       = new Client();
        $this->logger     = LogService:: logger('Sapo-api');
    }

    /**
     * Lấy thông tin shop chi tiết từ Sapo
     * https://support.sapo.vn/store
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getShopInfo(array $params)
    {
        $shopName     = data_get($params, 'shop_name');
        $clientId     = data_get($params, 'client_id');
        $clientSecret = data_get($params, 'client_secret');

        $baseUrl = $this->makeUrlApi($shopName, $clientId, $clientSecret);
        $path    = $baseUrl . 'admin/store.json';

        $paramsRequest = [];
        $respone = $this->request($path, $paramsRequest, 'GET');
        return $respone;
    }


    /**
     * Lấy dữ liệu danh sách đơn từ Sapo
     * https://open.Sapo.vn/docs/docs/current/api-references/order-api-v2/#order-listing-v2
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrders(array $params)
    {
        $paramsRequest = [
            'limit'          => data_get($params, 'limit'),
            'page'           => data_get($params, 'page'),
            'filter_date_by' => data_get($params, 'filter_date_by'),
            'order_by'       => data_get($params, 'order_by'),
            'access_token'   => data_get($params, 'access_token')
        ];
        $respone = $this->request("integration/v2/orders", $paramsRequest, 'GET');
        return $respone;
    }

    /**
     * Lấy dữ liệu chi tiết đơn
     * https://developers.tiktok-shops.com/documents/document/237427
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrderDetails(array $params)
    {
        $shopName     = data_get($params, 'shop_name');
        $clientId     = data_get($params, 'client_id');
        $clientSecret = data_get($params, 'client_secret');
        $orderId      = data_get($params, 'order_id');

        $baseUrl = $this->makeUrlApi($shopName, $clientId, $clientSecret);
        $path    = $baseUrl . "admin/orders/{$orderId}.json";

        $respone = $this->request($path, [], 'GET');
        return $respone;
    }

    /**
     * Get Order Fulfillments
     * https://api-doc.shopbase.com/#tag/Fulfillment/operation/retrieves-fulfillments-associated-with-an-order
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function getOrderFulfillments(array $params)
    {
        $shopName      = data_get($params, 'shop_name');
        $clientId      = data_get($params, 'client_id');
        $clientSecret  = data_get($params, 'client_secret');
        $orderId       = data_get($params, 'order_id');

        $paramsRequest = [];

        $baseUrl = $this->makeUrlApi($shopName, $clientId, $clientSecret);
        $path    = $baseUrl . "admin/orders/{$orderId}/fulfillments.json";

        $respone = $this->request($path, $paramsRequest, 'GET');
        return $respone;
    }


    /**
     * Lấy dữ liệu chi tiết sản phẩm  đơn
     * https://open.Sapo.vn/docs/docs/current/api-references/order-api-v2/#order-details-v2
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrderItemDetails(array $params)
    {
        $paramsRequest = [
            'order_id' => data_get($params, 'order_id'),
            'access_token' => data_get($params, 'access_token')
        ];
        $respone = $this->request('order/items/get', $paramsRequest, 'GET');
        return $respone;
    }


    /**
     * Get List product
     * https://support.sapo.vn/product
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getItems(array $params)
    {
        $shopName      = data_get($params, 'shop_name');
        $clientId      = data_get($params, 'client_id');
        $clientSecret  = data_get($params, 'client_secret');
        $createTimeMin = data_get($params, 'created_at_min');
        $createTimeMax = data_get($params, 'created_at_max');

        $paramsRequest = [];

        if ($createTimeMin && $createTimeMax) {
            $paramsRequest['created_at_min'] = $createTimeMin;
            $paramsRequest['created_at_max'] = $createTimeMax;
        }

        $baseUrl = $this->makeUrlApi($shopName, $clientId, $clientSecret);
        $path    = $baseUrl . 'admin/products.json';

        $respone = $this->request($path, $paramsRequest, 'GET');
        return $respone;
    }

    /**
     * Get Count List product
     * https://support.sapo.vn/product
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getItemsCount(array $params)
    {
        $shopName      = data_get($params, 'shop_name');
        $clientId      = data_get($params, 'client_id');
        $clientSecret  = data_get($params, 'client_secret');
        $createTimeMin = data_get($params, 'created_at_min');
        $createTimeMax = data_get($params, 'created_at_max');

        $paramsRequest = [];

        if ($createTimeMin && $createTimeMax) {
            $paramsRequest['created_at_min'] = $createTimeMin;
            $paramsRequest['created_at_max'] = $createTimeMax;
        }

        $baseUrl = $this->makeUrlApi($shopName, $clientId, $clientSecret);
        $path    = $baseUrl . 'admin/products/count.json';

        $respone = $this->request($path, $paramsRequest, 'GET');
        return $respone;
    }

    /**
     * Get Detail Product From Sapo
     * 
     * https://support.sapo.vn/phuong-thuc-get-cua-product#show
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getItemDetail(array $params)
    {
        $shopName     = data_get($params, 'shop_name');
        $clientId     = data_get($params, 'client_id');
        $clientSecret = data_get($params, 'client_secret');
        $productId    = data_get($params, 'product_id');

        $baseUrl = $this->makeUrlApi($shopName, $clientId, $clientSecret);
        $path    = $baseUrl . "admin/products/{$productId}.json";

        $respone = $this->request($path, [], 'GET');
        return $respone;
    }

    /**
     * Get Logistics Message From Sapo
     * 
     * https://developers.tiktok-shops.com/documents/document/237446
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getLogisticsMessage(array $params)
    {
        $paramsRequest = [
            'package_id'   => data_get($params, 'package_id'),
            'access_token' => data_get($params, 'access_token')
        ];
        $respone = $this->request("api/fulfillment/detail", $paramsRequest, 'GET');
        return $respone;
    }

    /**
     * Update Product Stock From Sapo
     * 
     * https://developers.tiktok-shops.com/documents/document/237486
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function updateProductStock(array $params)
    {
        $shopName     = data_get($params, 'shop_name');
        $clientId     = data_get($params, 'client_id');
        $clientSecret = data_get($params, 'client_secret');
        $skuId        = data_get($params, 'sku_id');
        $body         = data_get($params, 'body');

        $paramsRequest = [
            'body' => $body
        ];

        $baseUrl = $this->makeUrlApi($shopName, $clientId, $clientSecret);
        $path    = $baseUrl . "admin/variants/{$skuId}.json";

        $respone = $this->request($path, $paramsRequest, 'PUT');
        return $respone;
    }

    /**
     * Lấy Access Token Sapo bằng code Sapo trả về qua call back URL
     * https://open.Sapo.com/apps/doc/api?spm=a1zq7z.man108520.site_detail.1.3ae87c73rID2C8&path=%2Fauth%2Ftoken%2Fcreate
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function getAccessToken(array $params)
    {
        $code = data_get($params, 'code');
        // Generate Access Token From API
        $paramsRequest = [
            'auth_code'       => $code,
            'grant_type'      => 'authorized_code',
            'redirect_uri'    => url("marketplaces/". Marketplace::CODE_SHOPBASE . "/oauth-callback"),
            // 'redirect_uri' => 'https://api.m28.gobizdev.com/marketplaces/Sapo/oauth-callback',
            'app_key'         => config('services.sapo.client_id'),
            'app_secret'      => config('services.sapo.client_secret'),
        ];
        $respone = $this->request(config('services.sapo.authorization_uri') . '/api/v2/token/get', $paramsRequest, "GET");
        return $respone;
    }

    /**
     * Get Seller Info
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function getSellerInfo(array $params)
    {
        $accessToken = data_get($params, 'access_token');
        // Generate Access Token From API
        $paramsRequest = [
            'access_token' => $accessToken
        ];
        $respone = $this->request("api/shop/get_authorized_shop", $paramsRequest, "GET");
        return $respone;
    }

    /**
     * Make Webhook For Shop
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function createWebhook(array $params)
    {
        $shopName      = data_get($params, 'shop_name');
        $clientId      = data_get($params, 'client_id');
        $clientSecret  = data_get($params, 'client_secret');
        $body          = data_get($params, 'body');

        $paramsRequest = [
            'body' => $body
        ];

        $baseUrl = $this->makeUrlApi($shopName, $clientId, $clientSecret);
        $path    = $baseUrl . 'admin/webhooks.json';

        $respone = $this->request($path, $paramsRequest);
        return $respone;
    }

    /**
     * Get List Of Webhook For Shop
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function getWebhookList(array $params)
    {
        $shopName      = data_get($params, 'shop_name');
        $clientId      = data_get($params, 'client_id');
        $clientSecret  = data_get($params, 'client_secret');

        $paramsRequest = [];

        $baseUrl = $this->makeUrlApi($shopName, $clientId, $clientSecret);
        $path    = $baseUrl . 'admin/webhooks.json';

        $respone = $this->request($path, $paramsRequest, 'GET');
        return $respone;
    }

    /**
     * Send request
     * @param string $path
     * @param array $params
     * @param string $method
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function request($path, array $params = [], $method = 'post')
    {
        $body  = data_get($params, 'body');

        // Nếu là phương thức GET thì build query

        $dataOption = [
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        $method = strtolower($method);
        if ($method == 'post' || $method == 'put') {
            $dataOption['json'] = $body;
            if (isset($params['body'])) {
                unset($params['body']);
            }
        }

        $path = $path . '?' . http_build_query($params);

        try {
            $response = $this->http->{$method}($path, $dataOption);
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            $success = !empty($data) && empty($data['error']);
            $res = new RestApiResponse($success, $data, ['body' => $body]);

            if (!$res->success()) {
                $this->logger->error('REQUEST_ERROR', ['body' => $body]);
                throw new RestApiException($res);
            }

            return $res;
        } catch (Throwable $exception) {
            $data = [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'path' => $path,
                'params' => $params,
            ];

            $this->logger->error('REQUEST_EXCEPTION', $data);
            throw new RestApiException(new RestApiResponse(false, $data));
        }
    }

    /**
     * Make Url Api
     *
     * @param string $shopName
     * @param string $clientId
     * @param string $clientSecret
     * @return string
     */
    protected function makeUrlApi($shopName, $clientId, $clientSecret)
    {
        $url = "https://{$clientId}:{$clientSecret}@{$shopName}.{$this->apiUrl}/";
        return $url;
    }

    /**
     * Tạo Authorization authen Lazada Webhook
     * https://open.lazada.com/apps/doc/doc?nodeId=29526&docId=120168
     *
     * @param string $dataRawWebhook Data gốc lazada bắn về qua webhook
     * @return string
     */
    public function makeAuthorization(string $dataRawWebhook, $clientSecret)
    {
        return base64_encode(hash_hmac('sha256', $dataRawWebhook, $clientSecret, true));
    }
}
