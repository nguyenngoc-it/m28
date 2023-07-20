<?php

namespace Modules\Tiki\Services;

use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;

interface TikiApiInterface
{

  /**
     * Lấy dữ liệu danh sách đơn từ Tiki
     * https://open.tiki.vn/docs/docs/current/api-references/order-api-v2/#order-listing-v2
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrders(array $params);

   /**
     * Lấy dữ liệu chi tiết đơn 
     * https://open.Tiki.com/apps/doc/api?path=%2Forder%2Fget
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrderDetails(array $params);

    /**
     * Lấy dữ liệu chi tiết sản phẩm  đơn 
     * https://open.Tiki.com/apps/doc/api?path=%2Forder%2Fitems%2Fget
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrderItemDetails(array $params);


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
     * Lấy Access Token Tiki bằng code Tiki trả về qua call back URL
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function getAccessToken(array $params);

    /**
     * Lấy Access Token Tiki bằng refresh_token
     * https://open.tiki.vn/docs/docs/current/oauth-2-0/auth-flows/refresh-token/
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function refreshToken(array $params);

    /**
     * Tạo Token Client Credentials
     * https://www.jetbrains.com/help/space/client-credentials.html#how-to-implement
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function makeClientCredentials(array $params);

    /**
     * Create a queue with a name on your choice
     * https://open.tiki.vn/docs/docs/current/api-references/event-queue-api/
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function createQueue(array $params);

    /**
     * Create a subscribe an event type to queue
     * https://open.tiki.vn/docs/docs/current/api-references/event-queue-api/
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function createSubscription(array $params);

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
    public function pullEvents(array $params);

    /**
     * Get Seller Info
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function getSellerInfo(array $params);

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
