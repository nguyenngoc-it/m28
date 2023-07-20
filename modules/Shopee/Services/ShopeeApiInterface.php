<?php

namespace Modules\Shopee\Services;

use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;

interface ShopeeApiInterface
{
    /**
     * Test connection
     *
     * @throws RestApiException
     */
    public function test();

    /**
     * Get the logistics tracking information of an order
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getLogisticsMessage(array $params);

    /**
     * Get detailed information about one or more orders based on OrderIDs
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrderDetails(array $params);

    /**
     * Get all supported logistic channels
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getLogistics(array $params);


    /**
     * Use this call to get a list of items
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getItems(array $params);


    /**
     * Use this call to get detail of item
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getItemDetail(array $params);

    /**
     * Get all the logistics info of an order to Init.
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getLogisticInfo(array $params);

    /**
     * Use this call to initiate logistics including arrange Pickup, Dropoff or shipment for non-integrated logistic channel
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function logisticInit(array $params);

    /**
     * Use this API to get airway bill for orders. AirwayBill is only fetchable when the order status is under READY_TO_SHIP and RETRY_SHIP.
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getAirwayBill(array $params);

    /**
     * Send request
     *
     * @param string $path
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function request($path, array $params = []);
}
