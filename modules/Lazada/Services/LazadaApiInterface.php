<?php

namespace Modules\Lazada\Services;

use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;

interface LazadaApiInterface
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
     * Lấy dữ liệu chi tiết đơn
     * https://open.lazada.com/apps/doc/api?path=%2Forders%2Fget
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrderList(array $params);

    /**
     * Lấy dữ liệu chi tiết đơn
     * https://open.lazada.com/apps/doc/api?path=%2Forder%2Fget
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrderDetails(array $params);

    /**
     * Lấy dữ liệu chi tiết sản phẩm  đơn
     * https://open.lazada.com/apps/doc/api?path=%2Forder%2Fitems%2Fget
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrderItemDetails(array $params);

    /**
     * Lấy dữ liệu thông tin vận đơn  đơn
     * https://open.lazada.com/apps/doc/api?path=%2Flogistic%2Forder%2Ftrace
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrderTrace(array $params);

    /**
     * Lấy dữ liệu danh sách sản phẩm từ Lazada
     * https://open.lazada.com/apps/doc/api?path=%2Fproducts%2Fget
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getItems(array $params);


    /**
     * Lấy dữ liệu chi tiết sản phẩm từ Lazada
     * https://open.lazada.com/apps/doc/api?path=%2Fproduct%2Fitem%2Fget
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getItemDetail(array $params);

    /**
     * Lấy Access Token Lazada bằng code Lazada trả về qua call back URL
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function getAccessToken(array $params);

    /**
     * Tạo Authorization authen Lazada Webhook
     * https://open.lazada.com/apps/doc/doc?nodeId=29526&docId=120168
     *
     * @param string $dataRawWebhook Data gốc lazada bắn về qua webhook
     * @return string
     */
    public function makeAuthorization(string $dataRawWebhook);

    /**
     * @param array $params
     * @return RestApiResponse|mixed
     * @throws RestApiException
     */
    public function updateProductLazada(array $params);

    /**
     * @param array $params
     * @return mixed
     */
    public function getSeller(array $params);
}
