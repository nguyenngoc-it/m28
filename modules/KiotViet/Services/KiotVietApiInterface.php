<?php

namespace Modules\KiotViet\Services;

use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;
use Modules\Store\Models\Store;

interface KiotVietApiInterface
{
    /**
     * Test connection
     *
     * @throws RestApiException
     */
    public function test();

    public function setHeaders(array $headers);

    /**
     * Get the logistics tracking information of an order
     * @param array $param
     * @param Store $store
     * @return RestApiResponse
     * @throws RestApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getLogisticsMessage(array $param, Store $store);

    /**
     * Use this call to get a list of items
     *
     * @param array $params
     * @param Store $store
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getItems(array $params, Store $store);


    /**
     * Use this call to get detail of item
     *
     * @param int $id
     * @param Store $store
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getItemDetail(int $id = 0, Store $store);


    /**
     * Lấy danh sách hoá đơn
     * https://www.kiotviet.vn/huong-dan-su-dung-public-api-retail/#2121-lay-danh-sach-hoa-don-
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getInvoices(array $params, Store $store);

    /**
     * Use this call to get detail of invoice
     *
     * @param int $id
     * @param Store $store
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getInvoiceDetail(int $id = 0, Store $store);

    /**
     * Lấy danh sách đặt hàng
     * https://www.kiotviet.vn/huong-dan-su-dung-public-api-retail/#251-lay-danh-sach-dat-hang
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrders(array $params, Store $store);

    /**
     * Lấy danh sách đặt hàng
     * https://www.kiotviet.vn/huong-dan-su-dung-public-api-retail/#252-lay-chi-tiet-dat-hang
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrder(array $params, Store $store);

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
    public function updateProduct(int $id = 0, Store $store, $params);

    /**
     * get list Branches
     * @param Store $store
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getBranches(Store $store, $params = []);

    /**
     * Send request
     *
     * @param string $path
     * @param array $headers
     * @param array $params
     * @param string $method
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function request($path, array $headers, array $params = [], $method = 'post');


    /**
     * Get setting data for Kiotviet channel
     * @param  string|null $clientId     [Client id of shop at Kiotviet config]
     * @param  string|null $clientSecret [Client secret of shop at Kiotviet config]
     * @param  string|null $shopName     [Shop's name at Kiotviet]
     * @return array                    [data setting]
     */
    public function getSettingKiotViet(string $clientId = null, string $clientSecret = null, string $shopName = null);
}
