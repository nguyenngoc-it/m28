<?php

namespace Modules\Shopee\Services;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Throwable;

class ShopeeApi implements ShopeeApiInterface
{
    /**
     * @var
     */
    protected $apiUrl = 'https://partner.shopeemobile.com';

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
        $this->apiUrl = rtrim($config['api_url'], '/');
        $this->partnerId = (int)$config['partner_id'];
        $this->partnerKey = $config['partner_key'];
        $this->http = new Client(['base_uri' => $this->apiUrl . '/']);
        $this->logger = LogService::logger('shopee-api');
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
     * Get all supported logistic channels
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getLogistics(array $params)
    {
        return $this->request('api/v1/logistics/channel/get', $params);
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
        return $this->request('api/v1/items/get', $params);
    }

    /**
     * Use this call to get detail of item
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getItemDetail(array $params)
    {
        return $this->request('api/v1/item/get', $params);
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
     * Get all the logistics info of an order to Init.
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getLogisticInfo(array $params)
    {
        return $this->request('api/v1/logistics/init_info/get', $params);
    }

    /**
     * Use this call to initiate logistics including arrange Pickup, Dropoff or shipment for non-integrated logistic channel
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function logisticInit(array $params)
    {
        return $this->request('api/v1/logistics/init', $params);
    }

    /**
     * Use this API to get airway bill for orders. AirwayBill is only fetchable when the order status is under READY_TO_SHIP and RETRY_SHIP.
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getAirwayBill(array $params)
    {
        return $this->request('api/v1/logistics/airway_bill/get_mass', $params);
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
        $params = array_merge($params, [
            'partner_id' => $this->partnerId,
            'timestamp' => time(),
        ]);

        if(isset($params['shopid'])) {
            $params['shopid'] = intval($params['shopid']);
        }

        try {
            $response = $this->http->{$method}($path, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => $this->makeSign($path, $params),
                ],
                'json' => $params,
            ]);
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
    protected function makeSign($path, array $params)
    {
        $data = $this->apiUrl.'/'.$path.'|'.json_encode($params);

        return hash_hmac('sha256', $data, $this->partnerKey);
    }
}
