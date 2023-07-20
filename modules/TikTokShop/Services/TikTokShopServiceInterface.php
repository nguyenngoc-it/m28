<?php

namespace Modules\TikTokShop\Services;

use Gobiz\Support\RestApiException;
use Gobiz\Validation\ValidationException;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Models\Order;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Store\Models\Store;
use Modules\User\Models\User;

interface TikTokShopServiceInterface
{
    /**
     * Get TikTokShop api
     *
     * @return TikTokShopApiInterface
     */
    public function api();
    /**
     * Đồng bộ đơn TikTokShop theo danh sách order code
     *
     * @param Store $store
     * @return Order[]|null
     * @throws RestApiException
     * @throws WorkflowException
     */
    public function syncOrders(Store $store);

    /**
     * Thực hiện đồng bộ đơn
     *
     * @param Store $store
     * @param array $input Thông tin order theo response của TikTokShop webhook
     * @return Order
     * @throws ValidationException
     */
    public function syncOrder(Store $store, array $orderInput);

    /**
     * Đồng bộ toàn bộ sản phẩm từ shopee theo merchant
     *
     * @param Store $store
     * @param int $merchantId
     * @param boolean $filterUpdateTime
     * @return array|null
     */
    public function syncProducts(Store $store, $filterUpdateTime = true);

    /**
     * Đồng bộ sản phẩm từ TikTokShop
     * @param Store $store
     * @param array $TikTokShopItemId
     * @return array|Product[]
     * @throws RestApiException
     * @throws \Modules\Marketplace\Services\MarketplaceException
     */
    public function syncProduct(Store $store, $TikTokShopItemId);

    /**
     * Tạo thông tin token kết nối
     *
     * @param array $input
     * @return array
     */
    public function makeToken(array $input);

    /**
     * Mapping trạng thái vận chuyển
     *
     * @param string $logisticsStatus
     * @return string|null
     */
    public function mapFreightBillStatus($logisticsStatus);

   /**
     * Đồng bộ mã vận đơn TikTokShop
     *
     * @param Store $store
     * @param Order $order
     * @param string $trackingNo
     * @return FreightBill[]
     * @throws RestApiException
     */
    public function syncFreightBill(Store $store, Order $order, string $trackingNo);

    /**
     * @param Store $store
     * @return Store $store
     * @throws RestApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function refreshToken(Store $store);
    
    /**
    * lấy link intem của tiktok shop
     * @param int $shippingPartnerId
     * @param array $freightBillCodes
     * @return array|string
     */
    public function downloadShippingDocument($shippingPartnerId, $freightBillCodes);
}
