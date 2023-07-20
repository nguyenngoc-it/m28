<?php

namespace Modules\Sapo\Services;

use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;

interface SapoApiInterface
{

  /**
     * Lấy thông tin shop chi tiết từ Sapo
     * https://api-doc.shopbase.com/#tag/Shop/operation/retrieves-the-shop's-configuration
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getShopInfo(array $params);

  /**
     * Lấy dữ liệu danh sách đơn từ Sapo
     * https://open.Sapo.vn/docs/docs/current/api-references/order-api-v2/#order-listing-v2
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrders(array $params);

   /**
     * Lấy dữ liệu chi tiết đơn 
     * https://open.Sapo.com/apps/doc/api?path=%2Forder%2Fget
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrderDetails(array $params);

    /**
     * Get Order Fulfillments
     * https://api-doc.shopbase.com/#tag/Fulfillment/operation/retrieves-fulfillments-associated-with-an-order
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function getOrderFulfillments(array $params);

    /**
     * Lấy dữ liệu chi tiết sản phẩm  đơn 
     * https://open.Sapo.com/apps/doc/api?path=%2Forder%2Fitems%2Fget
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
     * Get Count List product
     * https://support.sapo.vn/product
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getItemsCount(array $params);


    /**
     * Use this call to get detail of item
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getItemDetail(array $params);

    /**
     * Get Logistics Message From Sapo
     * 
     * https://developers.tiktok-shops.com/documents/document/237446
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getLogisticsMessage(array $params);

    /**
     * Update Product Stock From Sapo
     * 
     * https://developers.tiktok-shops.com/documents/document/237486
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function updateProductStock(array $params);

    /**
     * Lấy Access Token Sapo bằng code Sapo trả về qua call back URL
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function getAccessToken(array $params);

    /**
     * Get Seller Info
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function getSellerInfo(array $params);

    /**
     * Make Webhook For Shop
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function createWebhook(array $params);

    /**
     * Get List Of Webhook For Shop
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function getWebhookList(array $params);

    /**
     * Send request
     *
     * @param string $path
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function request($path, array $params = []);

    /**
     * Tạo Authorization authen Lazada Webhook
     * https://open.lazada.com/apps/doc/doc?nodeId=29526&docId=120168
     *
     * @param string $dataRawWebhook Data gốc lazada bắn về qua webhook
     * @return string
     */
    public function makeAuthorization(string $dataRawWebhook, $clientSecret);
}
