<?php

namespace Modules\TikTokShop\Services;

use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;

interface TikTokShopApiInterface
{

  /**
     * Lấy dữ liệu danh sách đơn từ TikTokShop
     * https://open.TikTokShop.vn/docs/docs/current/api-references/order-api-v2/#order-listing-v2
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrders(array $params);

   /**
     * Lấy dữ liệu chi tiết đơn 
     * https://open.TikTokShop.com/apps/doc/api?path=%2Forder%2Fget
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getOrderDetails(array $params);

    /**
     * Lấy dữ liệu chi tiết sản phẩm  đơn 
     * https://open.TikTokShop.com/apps/doc/api?path=%2Forder%2Fitems%2Fget
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
     * Get Logistics Message From TikTokShop
     * 
     * https://developers.tiktok-shops.com/documents/document/237446
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getLogisticsMessage(array $params);

    /**
     * Update Product Stock From TikTokShop
     * 
     * https://developers.tiktok-shops.com/documents/document/237486
     *
     * @param array $params
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function updateProductStock(array $params);

    /**
     * Lấy Access Token TikTokShop bằng code TikTokShop trả về qua call back URL
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function getAccessToken(array $params);

    /**
     * Lấy Access Token TikTokShop bằng code TikTokShop trả về qua call back URL
     * https://developers.tiktok-shops.com/documents/document/234120
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function refreshToken(array $params);

    /**
     * Get Seller Info
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function getSellerInfo(array $params);

    /**
     * Get Shipping Document
     *
     * @param array $params
     * @return RestApiResponse $respone
     * @throws RestApiException
     */
    public function getShippingDocument(array $params);

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
    public function makeAuthorization(string $dataRawWebhook);
}
