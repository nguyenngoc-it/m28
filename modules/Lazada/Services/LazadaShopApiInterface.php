<?php

namespace Modules\Lazada\Services;

use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;

interface LazadaShopApiInterface
{
    /**
     * Get ship info
     *
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getShopInfo();

    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getItems($params);

    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getItemBaseInfo($params);

    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getModelList($params);

    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrderDetails($params);

    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getLogistics($params = []);

    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function shipOrder($params);

    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getShippingParameter($params);

    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getTrackingInfo($params);

    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function createShippingDocument($params);

    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getShippingDocumentResult($params);

    /**
     * @param $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function downloadShippingDocument($params);
}
