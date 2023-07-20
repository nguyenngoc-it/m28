<?php

namespace Modules\Tiki\Services;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Modules\Marketplace\Services\Marketplace;
use Psr\Log\LoggerInterface;
use Throwable;

class TikiApi implements TikiApiInterface
{
    /**
     * @var
     */
    protected $apiUrl = 'https://api.tiki.vn';

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
        $this->apiUrl = config('services.tiki.api_url');
        $this->partnerId = config('services.tiki.client_id');
        $this->partnerKey = config('services.tiki.client_secret');
        $this->http = new Client(['base_uri' => $this->apiUrl . '/']);
        $this->logger = LogService::logger('tiki-api');
    }


    /**
     * Lấy dữ liệu danh sách đơn từ Tiki
     * https://open.tiki.vn/docs/docs/current/api-references/order-api-v2/#order-listing-v2
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
     * https://open.tiki.vn/docs/docs/current/api-references/order-api-v2/#order-details-v2
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrderDetails(array $params)
    {
        $paramsRequest = [
            'order_id'     => data_get($params, 'order_id'),
            'access_token' => data_get($params, 'access_token')
        ];
        $respone = $this->request("integration/v2/orders/{$paramsRequest['order_id']}", $paramsRequest, 'GET');
        return $respone;
    }


    /**
     * Lấy dữ liệu chi tiết sản phẩm  đơn
     * https://open.tiki.vn/docs/docs/current/api-references/order-api-v2/#order-details-v2
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
        $paramsRequest = [
            'limit'        => data_get($params, 'limit'),
            'page'         => data_get($params, 'page'),
            'access_token' => data_get($params, 'access_token')
        ];
        $respone = $this->request('integration/v2.1/products', $paramsRequest, 'GET');
        return $respone;
    }

    /**
     * Get Detail Product From Tiki
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
            'productId'    => data_get($params, 'productId'),
            'access_token' => data_get($params, 'access_token'),
            'includes'     => 'attributes,images'
        ];
        $respone = $this->request("integration/v2.1/products/{$paramsRequest['productId']}", $paramsRequest, 'GET');
        return $respone;
    }

    /**
     * Lấy Access Token Tiki bằng code Tiki trả về qua call back URL
     * https://open.Tiki.com/apps/doc/api?spm=a1zq7z.man108520.site_detail.1.3ae87c73rID2C8&path=%2Fauth%2Ftoken%2Fcreate
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
            'code'         => $code,
            'grant_type'   => 'authorization_code',
            'redirect_uri' => url("marketplaces/". Marketplace::CODE_TIKI . "/oauth-callback"),
            // 'redirect_uri' => 'https://api.m28.gobizdev.com/marketplaces/TIKI/oauth-callback',
            'client_id'    => config('services.tiki.client_id')
        ];
        $respone = $this->request('sc/oauth2/token', $paramsRequest);
        return $respone;
    }

    /**
     * Lấy Access Token Tiki bằng refresh_token
     * https://open.tiki.vn/docs/docs/current/oauth-2-0/auth-flows/refresh-token/
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
            'client_id'     => config('services.tiki.client_id'),
            'client_secret' => config('services.tiki.client_secret')
        ];
        $respone = $this->request('sc/oauth2/token', $paramsRequest);
        return $respone;
    }

    /**
     * Tạo Token Client Credentials
     * https://www.jetbrains.com/help/space/client-credentials.html#how-to-implement
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function makeClientCredentials(array $params)
    {
        // Generate Access Token From API
        $paramsRequest = [
            'grant_type'   => 'client_credentials',
            'client_id'    => config('services.tiki.client_id')
        ];
        $respone = $this->request('sc/oauth2/token', $paramsRequest);
        return $respone;
    }

    /**
     * Create a queue with a name on your choice
     * https://open.tiki.vn/docs/docs/current/api-references/event-queue-api/
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function createQueue(array $params)
    {
        $accessToken = data_get($params, 'access_token');
        // Generate Access Token From API
        $paramsRequest = [
            'access_token' => $accessToken,
            'name'         => data_get($params, 'name')
        ];
        $respone = $this->request('integration/v1/queues', $paramsRequest);
        return $respone;
    }

    /**
     * Create a subscribe an event type to queue
     * https://open.tiki.vn/docs/docs/current/api-references/event-queue-api/
     * 
     * https://open.tiki.vn/docs/docs/current/guides/tiki-theory/event-type/
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function createSubscription(array $params)
    {
        $accessToken = data_get($params, 'access_token');
        $queueCode   = data_get($params, 'queue_code');
        // Generate Access Token From API
        $paramsRequest = [
            'access_token' => $accessToken,
            'event_type'   => data_get($params, 'event_type')
        ];
        $respone = $this->request("integration/v1/queues/{$queueCode}/subscriptions", $paramsRequest);
        return $respone;
    }

    /**
     * Create a subscribe an event type to queue
     * https://open.tiki.vn/docs/docs/current/api-references/event-queue-api/
     * 
     * https://open.tiki.vn/docs/docs/current/guides/tiki-theory/event-type/
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function pullEvents(array $params)
    {
        $accessToken = data_get($params, 'access_token');
        $queueCode   = data_get($params, 'queue_code');
        // Generate Access Token From API
        $paramsRequest = [
            'access_token' => $accessToken,
            'ack_id'       => data_get($params, 'ack_id')
        ];
        $respone = $this->request("integration/v1/queues/{$queueCode}/events/pull", $paramsRequest);
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
        $respone = $this->request("integration/v2/sellers/me", $paramsRequest, "GET");
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
        $accessToken = data_get($params, 'access_token');

        $dataHeaders = [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => "Bearer {$accessToken}"
            ],
        ];

        if ($accessToken) {
            $dataHeaders = [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => "Bearer {$accessToken}"
                ],
            ]; 
        } else {
            $clientId     = config('services.tiki.client_id');
            $clientSecret = config('services.tiki.client_secret');
            $basicToken   = base64_encode("{$clientId}:{$clientSecret}");

            $dataHeaders = [
                'headers' => [
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                    'Authorization' => "Basic {$basicToken}"
                ],
                'form_params' => $params,
            ]; 
        }

        // Nếu là phương thức GET thì build query
        if (strtolower($method) == 'get') {
            $path = $path . '?' . http_build_query($params);
        } else {
            if ($accessToken) {
                $params = Arr::except($params, ['access_token']);
                $dataHeaders['json'] = $params;
            }
        }
        
        try {
            $response = $this->http->{$method}($path, $dataHeaders);
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
}
