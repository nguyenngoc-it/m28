<?php

namespace Modules\KiotViet\Services;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Throwable;
use Illuminate\Support\Arr;
use Modules\Store\Models\Store;

class KiotVietApi implements KiotVietApiInterface
{
    /**
     * @var
     */
    protected $apiUrl = 'https://public.kiotapi.com';

    /**
     * @var
     */
    protected $webhookUrl = '';

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

    protected $headers = [];

    protected $webhookEvents = [];

    protected $webhookEventList = ['product.update', 'order.update', 'invoice.update'];

    protected $store;

    protected $storeId = null;

    /**
     * KiotVietApi constructor
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $headers = [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json'
        ];
        $this->apiUrl     = data_get($config, 'api_url', $this->apiUrl);
        $this->webhookUrl = request()->getSchemeAndHttpHost() . '/webhook/kiotviet';
        $this->http       = new Client([
            'base_uri'    => $this->apiUrl,
            'headers'     => $headers,
            'http_errors' => false
        ]);
        $this->logger = LogService::logger('kiotviet-api');
    }

    /**
     * set optional headers
     * @param array $headers
     * @return array
     */
    public function setHeaders (array $headers)
    {
        $this->headers = $headers;
        return $this->headers;
    }

    /**
     * Test connection
     *
     * @throws RestApiException
     */
    public function test()
    {
        
    }

    /**
     * Get detailed information about one or more orders based on OrderIDs
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrderDetails(array $params)
    {
        return $this->request('api/v1/orders/detail', $params);
    }


    /**
     * Use this call to get a list of items
     * @param array $params
     * @param Store $store
     * @return RestApiResponse
     * @throws RestApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getItems(array $params, Store $store)
    {
        $paramRequest = [
            'pageSize'         => data_get($params, 'pageSize'),
            'currentItem'      => data_get($params, 'currentItem'),
            'orderBy'          => 'createdDate',
            'orderDirection'   => 'DESC'
        ];
        $query = http_build_query($paramRequest);
        $response = $this->requestKiotViet("products?{$query}", $store);
        return $response;
    }

    /**
     * Use this call to get detail of item
     *
     * @param int $id
     * @param Store $store
     * @return RestApiResponse
     * @throws RestApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getItemDetail(int $id = 0, Store $store)
    {
        $response = $this->requestKiotViet("products/{$id}", $store);
        return $response;
    }


    /**
     * Lấy danh sách hoá đơn
     * https://www.kiotviet.vn/huong-dan-su-dung-public-api-retail/#2121-lay-danh-sach-hoa-don-
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getInvoices(array $params, Store $store)
    {
        $paramRequest = [
            'status'         => data_get($params, 'status'),
            'pageSize'       => data_get($params, 'pageSize'),
            'currentItem'    => data_get($params, 'currentItem'),
            'createdDate'    => data_get($params, 'createdDate'),
            'orderBy'        => 'createdDate',
            'orderDirection' => 'DESC'
        ];
        $query = http_build_query($paramRequest);
        $response = $this->requestKiotViet("invoices?{$query}", $store);
        return $response;
    }

    /**
     * Use this call to get detail of invoice
     *
     * @param int $id
     * @param Store $store
     * @return RestApiResponse
     * @throws RestApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getInvoiceDetail(int $id = 0, Store $store)
    {
        $response = $this->requestKiotViet("invoices/{$id}", $store);
        return $response;
    }

    /**
     * Lấy danh sách đặt hàng
     * https://www.kiotviet.vn/huong-dan-su-dung-public-api-retail/#251-lay-danh-sach-dat-hang
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrders(array $params, Store $store)
    {
        $paramRequest = [
            'status'         => data_get($params, 'status'),
            'pageSize'       => data_get($params, 'pageSize'),
            'currentItem'    => data_get($params, 'currentItem'),
            'createdDate'    => data_get($params, 'createdDate'),
            'orderBy'        => 'createdDate',
            'orderDirection' => 'DESC'
        ];
        $query = http_build_query($paramRequest);
        $response = $this->requestKiotViet("orders?{$query}", $store);
        return $response;
    }


    /**
     * Lấy danh sách đặt hàng
     * https://www.kiotviet.vn/huong-dan-su-dung-public-api-retail/#252-lay-chi-tiet-dat-hang
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrder(array $params, Store $store)
    {
        $paramRequest = [
            'order_id' => data_get($params, 'order_id')
        ];
        $response = $this->requestKiotViet("orders/{$paramRequest['order_id']}", $store);
        return $response;
    }

    /**
     * Use this call to update product
     *
     * @param int $id
     * @param Store $store
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateProduct(int $id = 0, Store $store, $params)
    {
        return $this->requestKiotViet("products/{$id}", $store, $params, 'PUT');
    }

    /**
     * get list Branches
     * @param Store $store
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getBranches(Store $store, $params = [])
    {
        return $this->requestKiotViet("branches", $store, $params);
    }

    /**
     * requestKiotViet
     * @param string $endpoint
     * @param Store $store
     * @param string $method
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function requestKiotViet(string $endpoint, Store $store, $params = [], $method = 'GET')
    {
        $shopName = $store->getSetting('shop_name');
        $headers  = [
            'Retailer'      => $shopName,
            'Authorization' => "Bearer {$store->getSetting('access_token')}"
        ];
        $this->setHeaders($headers);

        $response = $this->request($endpoint, $this->headers, $params, $method);

        // Nếu Token hết hạn thì lấy lại token mới
        if ($response->getData('responseStatus.errorCode') == config('services.kiotviet.token_exception') ||
            $response->getData('message') == 'Unauthorized')
        {
            $clientId      = $store->getSetting('client_id');
            $clientSecret  = $store->getSetting('secret_key');
            $webhookEvents = $store->getSetting('webhook_events');
            $settings      = $this->getSettingKiotViet($clientId, $clientSecret, $shopName);

            // Update lại thông tin vào settings
            $settingAccessToken = data_get($settings, 'access_token', null);
            if ($settingAccessToken) {
                if ($webhookEvents && empty($settings['webhook_events'])) {
                    $settings['webhook_events'] = $webhookEvents;
                }
                $store->update(['settings' => $settings]);
            }

            $response = $this->request($endpoint, $this->headers, $params, $method);
        }

        return $response;
    }


    /**
     * Get the logistics tracking information of an order
     * @param array $param
     * @param Store $store
     * @return RestApiResponse
     * @throws RestApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getLogisticsMessage(array $param, Store $store)
    {
        $endpoint = "{$param['order_type']}s/code/{$param['order_code']}";
        $response = $this->requestKiotViet($endpoint, $store);
        return $response;
    }

    /**
     * @param string $path
     * @param array $headers
     * @param array $params
     * @param string $method
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function request($path, array $headers, array $params = [], $method = 'POST')
    {

        try {
            $options = [
                'headers' => $headers
            ];
            if(strtoupper($method) == 'GET') {
                $options['query'] = $params;
            } else {
                $options['json'] = $params;
            }
            $response = $this->http->request($method, $path, $options);

            $body    = $response->getBody()->getContents();
            $data    = json_decode($body, true);
            $success = !empty($data) && empty($data['error']);
            $res     = new RestApiResponse($success, $data, ['body' => $body]);

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
     * Get setting data for Kiotviet channel
     * @param  string|null $clientId     [Client id of shop at Kiotviet config]
     * @param  string|null $clientSecret [Client secret of shop at Kiotviet config]
     * @param  string|null $shopName     [Shop's name at Kiotviet]
     * @return array                    [data setting]
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSettingKiotViet(string $clientId = null, string $clientSecret = null, string $shopName = null)
    {
        $request = [
            'scopes'        => 'PublicApi.Access',
            'grant_type'    => 'client_credentials',
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
        ];

        /*
        Get access token from Kiotviet public API
        https://www.kiotviet.vn/huong-dan-su-dung-public-api-retail/#1-gioi-thieu
         */

        $response    = $this->http->request('POST', 'https://id.kiotviet.vn/connect/token', ['form_params' => $request]);
        $statuscode  = $response->getStatusCode();
        $bodyRes     = json_decode($response->getBody()->getContents(), true);

        if ($statuscode == 200) {
            $accessToken = data_get($bodyRes, 'access_token', null);
            $expiresIn   = data_get($bodyRes, 'expires_in', 0);
        } else {
            return [];
        }

        // Đăng ký webhook từ kiotviet
        $webhookEvents = $this->registerWebhook($shopName, $accessToken);

        $settings = [
            'client_id'                        => $clientId,
            'secret_key'                       => $clientSecret,
            'access_token'                     => $accessToken,
            'expire_at'                        => time() + intval($expiresIn),
            'shop_name'                        => $shopName,
            'marketplace_store_id'             => $this->storeId,
            'webhook_events'                   => $webhookEvents,
            'KIOTVIET_PRODUCT_LAST_UPDATED_AT' => null
        ];

        return $settings;
    }

    /**
     * [register Webhook events from kiotviet]
     * @param  string $shopName     [shop name from kiotviet]
     * @param  string $accessToken     [access token from kiotviet]
     * @return array [list webhook events registed]
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function registerWebhook(string $shopName, string $accessToken)
    {

        $headers = [
            'Retailer'      => $shopName,
            'Authorization' => "Bearer {$accessToken}"
        ];

        foreach ($this->webhookEventList as $webhookEvent) {

            // Đăng ký event
            $requestBody = [
                'Webhook' => [
                    'Type'        => $webhookEvent,
                    'Url'         => $this->webhookUrl,
                    'IsActive'    => true,
                    'Description' => ''
                ]
            ];

            $response   = $this->http->request('POST', "webhooks", ['headers' => $headers,'json' => $requestBody]);
            $bodyRes    = json_decode($response->getBody()->getContents(), true);
            $statuscode = $response->getStatusCode();

            if ($statuscode == 200) {
                $this->webhookEvents = Arr::add($this->webhookEvents, $webhookEvent, $bodyRes);
                $this->storeId = data_get($bodyRes, 'retailerId', null);
            } else {
                $this->logger->info('kiotviet-connect-fail', ['code' => $statuscode, 'event' => $webhookEvent, 'detail' => $bodyRes]);
            }
        }

        return $this->webhookEvents;
    }
}
