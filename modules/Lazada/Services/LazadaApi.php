<?php

namespace Modules\Lazada\Services;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Psr\Log\LoggerInterface;
use Throwable;

class LazadaApi implements LazadaApiInterface
{
    /**
     * @var
     */
    protected $apiUrl = 'https://api.lazada.vn/rest';
    protected $apiUrlAuth = 'https://auth.lazada.com/rest';

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
        $this->apiUrl     = Arr::get($config, 'api_url');
        $this->apiUrlAuth = Arr::get($config, 'api_url_auth');
        $this->partnerId  = Arr::get($config, 'client_id');
        $this->partnerKey = Arr::get($config, 'client_secret');
        $this->http       = new Client(['base_uri' => $this->apiUrl . '/']);
        $this->logger     = LogService::logger('lazada-api');
    }

    /**
     * Test connection
     *
     * @throws RestApiException
     */
    public function test()
    {
        $this->request('api/v1/shop/get_partner_shop');
    }

    /**
     * Lấy dữ liệu chi tiết đơn
     * https://open.lazada.com/apps/doc/api?path=%2Forders%2Fget
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrderList(array $params)
    {
        $subHour = data_get($params, 'sub_hour', 1);
        $paramsRequest = [
            'access_token'   => data_get($params, 'access_token'),
            'limit'          => data_get($params, 'limit'),
            'offset'         => data_get($params, 'offset'),
            'update_after'   => Carbon::now()->subHour($subHour)->toIso8601String(),
            'sort_by'        => 'updated_at',
            'sort_direction' => 'DESC',
        ];

        return $this->request('orders/get', $paramsRequest, 'GET');
    }

    /**
     * Lấy dữ liệu chi tiết đơn
     * https://open.lazada.com/apps/doc/api?path=%2Forder%2Fget
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrderDetails(array $params)
    {
        $paramsRequest = [
            'order_id' => data_get($params, 'order_id'),
            'access_token' => data_get($params, 'access_token')
        ];

        return $this->request('order/get', $paramsRequest, 'GET');
    }


    /**
     * Lấy dữ liệu chi tiết sản phẩm  đơn
     * https://open.lazada.com/apps/doc/api?path=%2Forder%2Fitems%2Fget
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

        return $this->request('order/items/get', $paramsRequest, 'GET');
    }

    /**
     * Lấy dữ liệu thông tin vận đơn  đơn
     * https://open.lazada.com/apps/doc/api?path=%2Flogistic%2Forder%2Ftrace
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrderTrace(array $params)
    {
        $paramsRequest = [
            'order_id' => data_get($params, 'order_id'),
            'access_token' => data_get($params, 'access_token')
        ];

        return $this->request('logistic/order/trace', $paramsRequest, 'GET');
    }


    /**
     * Lấy dữ liệu danh sách sản phẩm từ Lazada
     * https://open.lazada.com/apps/doc/api?path=%2Fproducts%2Fget
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getItems(array $params)
    {
        $paramsRequest = [
            'limit' => data_get($params, 'limit'),
            'offset' => data_get($params, 'offset'),
            'access_token' => data_get($params, 'access_token')
        ];
        return $this->request('products/get', $paramsRequest, 'GET');
    }

    /**
     * Lấy dữ liệu chi tiết sản phẩm từ Lazada
     * https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fitem%2Fget
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getItemDetail(array $params)
    {
        $paramsRequest = [
            'item_id' => data_get($params, 'item_id'),
            'access_token' => data_get($params, 'access_token')
        ];
        return $this->request('product/item/get', $paramsRequest, 'GET');
    }

    /**
     * Get the logistics tracking information of an order
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getLogisticsMessage(array $params)
    {
        return $this->request('api/v1/logistics/tracking', $params);
    }

    /**
     * Lấy Access Token Lazada bằng code Lazada trả về qua call back URL
     * https://open.lazada.com/apps/doc/api?spm=a1zq7z.man108520.site_detail.1.3ae87c73rID2C8&path=%2Fauth%2Ftoken%2Fcreate
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function getAccessToken(array $params)
    {
        $code          = data_get($params, 'code');
        $paramsRequest = [
            'code' => $code
        ];
        $http          = new Client(['base_uri' => $this->apiUrlAuth . '/']);
        return $this->request('auth/token/create', $paramsRequest, "post", $http);
    }

    /**
     * Send request
     * @param string $path
     * @param array $params
     * @param string $method
     * @param Client|null $http
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function request($path, array $params = [], $method = 'post', Client $http = null)
    {
        $method = strtolower($method);
        $params = array_merge($params, $this->makeCommonParams($path, $params));

        $options = [
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];
        if ($method == 'get') {
            $path = $path . '?' . http_build_query($params);
        }
        if ($method == 'post') {
            $options['json'] = $params;
        }

        $this->logger->debug('INPUT', $params);

        try {
            if (!$http) {
                $response = $this->http->{$method}($path, $options);
            } else {
                $response = $http->post($path, $options);
            }

            $body    = $response->getBody()->getContents();
            $data    = json_decode($body, true);
            $success = !empty($data) && empty($data['error']);
            $res     = new RestApiResponse($success, $data, ['body' => $body]);

            if (!$res->success()) {
                $this->logger->error('REQUEST_ERROR', ['body' => $body]);
                throw new RestApiException($res);
            }
            $this->logger->debug('RESPONSE', ['body' => $body]);

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
            if ((($v != null) || $v === 0) && ($k != 'sign')) {
                $sign .= $k . $v;
            } else {
                unset($params[$k]);
            }
        }

        $sign = "/" . $path . $sign;
        // dd($sign);

        return strtoupper(hash_hmac('sha256', $sign, $this->partnerKey));
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
        $debug       = config('services.lazada.debug');

        $params['app_key']     = $this->partnerId;
        $params['sign_method'] = 'sha256';
        $params['timestamp']   = Carbon::now()->timestamp . '000';

        $commonParams = [
            'app_key' => $this->partnerId,
            'timestamp' => data_get($params, 'timestamp'),
            'sign_method' => 'sha256'
        ];

        if ($accessToken != null) {
            $params['access_token']       = $accessToken;
            $commonParams['access_token'] = $accessToken;
        }
        if ($debug) {
            $params['debug']       = $debug;
            $commonParams['debug'] = $debug;
        }

        $commonParams['sign'] = $this->makeSign($path, $params);

        return $commonParams;
    }

    /**
     * @param array $params
     * @return RestApiResponse|mixed
     * @throws RestApiException
     */
    public function updateProductLazada(array $params)
    {
        $paramsRequest = [
            'payload' => data_get($params, 'payload'),
            'access_token' => data_get($params, 'access_token'),
        ];
        $response      = $this->request('product/update', $paramsRequest);
        return $response;

    }

    /**
     * @param array $params
     * @return RestApiResponse|mixed
     * @throws RestApiException
     */
    public function getSeller(array $params)
    {
        $paramsRequest = [
            'access_token' => data_get($params, 'access_token'),
        ];
        $respone       = $this->request('seller/get', $paramsRequest, 'GET');
        return $respone;

    }

}
