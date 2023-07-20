<?php

namespace Modules\Shopee\Services;

use Closure;
use Exception;
use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;
use Modules\Marketplace\Services\StoreConnectionInterface;
use Modules\Service;
use Modules\Shopee\Jobs\RefreshShopeeAccessTokenJob;

class ShopeeShopApi extends ShopeeApiV2 implements ShopeeShopApiInterface, StoreConnectionInterface
{
    /**
     * @var int
     */
    protected $shopId;

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * ShopeeShopApi constructor
     *
     * @param int $shopId
     * @param string $accessToken
     */
    public function __construct($shopId, $accessToken)
    {
        parent::__construct();

        $this->shopId = (int)$shopId;
        $this->accessToken = (string)$accessToken;
    }

    /**
     * Make common params
     *
     * @param string $apiPath
     * @return array
     */
    protected function makeCommonParams($apiPath)
    {
        $time = time();

        return [
            'partner_id' => $this->partnerId,
            'timestamp' => $time,
            'access_token' => $this->accessToken,
            'shop_id' => $this->shopId,
            'sign' => $this->makeSign($this->partnerId.$apiPath.$time.$this->accessToken.$this->shopId),
        ];
    }

    /**
     * @inheritDoc
     */
    protected function sendRequest(Closure $handler)
    {
        try {
            return parent::sendRequest($handler);
        } catch (RestApiException $exception) {
            $error = $exception->getResponse()->getData('error');
            $message = $exception->getResponse()->getData('message');

            if (Service::shopee()->detectApiError($error, $message) === Shopee::ERROR_ACCESS_TOKEN_INVALID) {
                dispatch(new RefreshShopeeAccessTokenJob($this->shopId, $this->accessToken));
            }

            throw $exception;
        }
    }

    /**
     * Test connection
     *
     * @throws Exception
     */
    public function testConnection()
    {
        $this->getShopInfo();
    }

    /**
     * Get ship info
     *
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getShopInfo()
    {
        return $this->getRequest('/api/v2/shop/get_shop_info');
    }

    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getItems($params)
    {
        return $this->getRequest('/api/v2/product/get_item_list', $params);
    }

    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getItemBaseInfo($params)
    {
        return $this->getRequest('/api/v2/product/get_item_base_info', $params);
    }

    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getModelList($params)
    {
        return $this->getRequest('/api/v2/product/get_model_list', $params);
    }

    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrderDetails($params)
    {
        return $this->getRequest('/api/v2/order/get_order_detail', $params);
    }

    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrderList($params)
    {
        return $this->getRequest('/api/v2/order/get_order_list', $params);
    }


    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getLogistics($params = [])
    {
        return $this->getRequest('/api/v2/logistics/get_channel_list', $params);
    }

    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function shipOrder($params)
    {
        return $this->postRequest('/api/v2/logistics/ship_order', $params);
    }

    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getShippingParameter($params)
    {
        return $this->getRequest('/api/v2/logistics/get_shipping_parameter', $params);
    }

    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getTrackingInfo($params)
    {
        return $this->getRequest('/api/v2/logistics/get_tracking_info', $params);
    }

    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function createShippingDocument($params)
    {
        return $this->postRequest('/api/v2/logistics/create_shipping_document', $params);
    }


    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getShippingDocumentResult($params)
    {
        return $this->postRequest('/api/v2/logistics/get_shipping_document_result', $params);
    }

    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function downloadShippingDocument($params)
    {
        return $this->postRequest('/api/v2/logistics/download_shipping_document', $params);
    }

    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function updateStock($params)
    {
        return $this->postRequest('/api/v2/product/update_stock', $params);
    }

    /**
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getWarehouseDetail()
    {
        return $this->getRequest('/api/v2/shop/get_warehouse_detail');
    }
}
