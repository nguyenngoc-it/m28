<?php

namespace Modules\TikTokShop\Services;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Modules\Marketplace\Services\Marketplace;
use Psr\Log\LoggerInterface;
use Throwable;

class TikTokShopApi implements TikTokShopApiInterface
{
    /**
     * @var
     */
    protected $apiUrl = 'https://open-api.tiktokglobalshop.com';

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
        $this->apiUrl     = config('services.tiktokshop.api_url');
        $this->partnerId  = config('services.tiktokshop.client_id');
        $this->partnerKey = config('services.tiktokshop.client_secret');
        $this->http       = new Client(['base_uri' => $this->apiUrl . '/']);
        $this->logger     = LogService:: logger('tiktokshop-api');
    }


    /**
     * Lấy dữ liệu danh sách đơn từ TikTokShop
     * https://open.TikTokShop.vn/docs/docs/current/api-references/order-api-v2/#order-listing-v2
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
        $paramsRequest = [
            'shop_id'      => data_get($params, 'shop_id'),
            'body' => [
                'order_id_list'=> [data_get($params, 'order_id')],
            ],
            'access_token' => data_get($params, 'access_token')
        ];
        $respone = $this->request("api/orders/detail/query", $paramsRequest);
        return $respone;
    }


    /**
     * Lấy dữ liệu chi tiết sản phẩm  đơn
     * https://open.TikTokShop.vn/docs/docs/current/api-references/order-api-v2/#order-details-v2
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
     * Use this call to get a list of items
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getItems(array $params)
    {
        $createTimeFrom = data_get($params, 'create_time_from');
        $createTimeTo   = data_get($params, 'create_time_to');
        $paramsRequest = [
            'shop_id'       => data_get($params, 'shop_id'),
            'page_size'     => data_get($params, 'page_size'),
            'page_number'   => data_get($params, 'page_number'),
            'search_status' => TikTokShop::PRODUCT_STATUS_LIVE,
            'access_token'  => data_get($params, 'access_token')
        ];

        if ($createTimeFrom && $createTimeTo) {
            $paramsRequest['create_time_from'] = $createTimeFrom;
            $paramsRequest['create_time_to']   = $createTimeTo;
        }
        $respone = $this->request('api/products/search', $paramsRequest);
        return $respone;
    }

    /**
     * Get Detail Product From TikTokShop
     * 
     * 
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getItemDetail(array $params)
    {
        $paramsRequest = [
            'product_id'   => data_get($params, 'product_id'),
            'access_token' => data_get($params, 'access_token')
        ];
        $respone = $this->request("api/products/details", $paramsRequest, 'GET');
        return $respone;
    }

    /**
     * Get Logistics Message From TikTokShop
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
     * Update Product Stock From TikTokShop
     * 
     * https://developers.tiktok-shops.com/documents/document/237486
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function updateProductStock(array $params)
    {
        $paramsRequest = [
            'shop_id'      => data_get($params, 'shop_id'),
            'product_id'   => data_get($params, 'product_id'),
            'body'         => data_get($params, 'body'),
            'access_token' => data_get($params, 'access_token')
        ];
        $respone = $this->request("api/products/stocks", $paramsRequest, "PUT");
        return $respone;
    }

    /**
     * Lấy Access Token TikTokShop bằng code TikTokShop trả về qua call back URL
     * https://developers.tiktok-shops.com/documents/document/234120
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
            'redirect_uri'    => url("marketplaces/". Marketplace::CODE_TIKTOKSHOP . "/oauth-callback"),
            // 'redirect_uri' => 'https://api.m28.gobizdev.com/marketplaces/TIKTOKSHOP/oauth-callback',
            'app_key'         => config('services.tiktokshop.client_id'),
            'app_secret'      => config('services.tiktokshop.client_secret'),
        ];
        $respone = $this->request(config('services.tiktokshop.authorization_uri') . '/api/v2/token/get', $paramsRequest, "GET");
        return $respone;
    }

    /**
     * Lấy Access Token TikTokShop bằng code TikTokShop trả về qua call back URL
     * https://developers.tiktok-shops.com/documents/document/234120
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function refreshToken(array $params)
    {
        // Generate Access Token From API
        $paramsRequest = [
            'grant_type'    => 'refresh_token',
            'refresh_token' => data_get($params, 'refresh_token'),
            'app_key'       => config('services.tiktokshop.client_id'),
            'app_secret'    => config('services.tiktokshop.client_secret'),
        ];
        $respone = $this->request(config('services.tiktokshop.authorization_uri') . '/api/v2/token/refresh', $paramsRequest, "GET");
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
     * Get Shipping Document
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function getShippingDocument(array $params)
    {
        $accessToken = data_get($params, 'access_token');
        // Generate Access Token From API
        $paramsRequest = [
            'access_token'  => $accessToken,
            'order_id'      => data_get($params, 'order_id'),
            'shop_id'       => data_get($params, 'shop_id'),
            'document_type' => 'SHIPPING_LABEL',
            'document_size' => 'A6',
        ];
        $respone = $this->request("api/logistics/shipping_document", $paramsRequest, "GET");
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
        if ($body) {
            unset($params['body']);
        }
        // Make Common Params
        $params = array_merge($params, $this->makeCommonParams($path, $params));

        // Nếu là phương thức GET thì build query

        $path = $path . '?' . http_build_query($params);

        $dataOption = [
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        $method = strtolower($method);
        if ($method == 'post' || $method == 'put') {
            $dataOption['json'] = $body;
        }

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
     * @param string $path
     * @param array $params
     * @return string
     */
    protected function makeSign(string $path, array $params)
    {
        $sign = '';

        ksort($params);
        foreach ($params as $k => $v) {
            if ((($v != null) || $v === 0) && ($k != 'sign' || $k != 'access_token')) {
                $sign .= $k . $v;
            } else {
                unset($params[$k]);
            }
        }

        $sign = $this->partnerKey . "/" . $path . $sign . $this->partnerKey;
        // dd($sign);

        return hash_hmac('sha256', $sign, $this->partnerKey);
    }

    /**
     * Tạo các param mặc định khi call API của Lazada
     *
     * @param string $path
     * @param array $params
     * @return array $commonParams
     */
    protected function makeCommonParams(string $path, array $params)
    {
        $accessToken = data_get($params, 'access_token', null);

        $params['app_key'] = $this->partnerId;
        $params['timestamp'] = Carbon::now()->timestamp;

        $commonParams = [
            'app_key' => $this->partnerId,
            'timestamp' => data_get($params, 'timestamp')
        ];

        if ($accessToken != null) {
            $commonParams['access_token'] = $accessToken;
        }

        unset($params['access_token']);

        $commonParams['sign'] = $this->makeSign($path, $params);

        return $commonParams;
    }

    /**
     * Tạo Authorization authen Lazada Webhook
     * https://open.lazada.com/apps/doc/doc?nodeId=29526&docId=120168
     *
     * @param string $dataRawWebhook Data gốc lazada bắn về qua webhook
     * @return string
     */
    public function makeAuthorization(string $dataRawWebhook)
    {
        $base = $this->partnerId . $dataRawWebhook;
        return hash_hmac('sha256', $base, $this->partnerKey);
    }
}
