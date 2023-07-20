<?php

namespace Modules\Shopee\Services;

use Gobiz\Support\RestApiException;
use Gobiz\Validation\ValidationException;
use Modules\FreightBill\Models\FreightBill;
use Modules\Marketplace\Services\MarketplaceException;
use Modules\Order\Models\Order;
use Modules\Product\Models\Sku;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Stock\Models\Stock;
use Modules\Store\Models\Store;
use Modules\User\Models\User;

interface ShopeeServiceInterface
{
    /**
     * Get shopee api
     *
     * @return ShopeeApiInterface
     */
    public function api();

    /**
     * Get shopee public api
     *
     * @return ShopeePublicApiInterface
     */
    public function publicApi();

    /**
     * Mapping trạng thái vận chuyển
     *
     * @param string $logisticsStatus
     * @return string|null
     */
    public function mapFreightBillStatus($logisticsStatus);

    /**
     * Đồng bộ đơn shopee theo danh sách id đơn shopee
     *
     * @param int $shopId
     * @param array $orderInputs = [['order_sn' => 'xxx', 'order_status' => 'xxx'], ...]
     * @return Order[]|null
     */
    public function syncOrders($shopId, array $orderInputs);

    /**
     * Đồng bộ mã vận đơn shopee
     *
     * @param int $shopId
     * @param string $orderCode
     * @param string $trackingNo
     * @return FreightBill[]
     */
    public function syncFreightBill($shopId, $orderCode, $trackingNo);

    /**
     * Thực hiện đồng bộ đơn
     *
     * @param Store $store
     * @param array $input Thông tin order theo response của shopee api /orders/detail
     * @param User $creator
     * @return Order
     * @throws ValidationException
     */
    public function syncOrder(Store $store, array $input, User $creator);

    /**
     * Đồng bộ toàn bộ sản phẩm từ shopee theo merchant
     *
     * @param int $storeId
     * @param int $merchantId
     * @param boolean $filterUpdateTime
     * @return array|null
     */
    public function syncProducts($storeId, $merchantId, $filterUpdateTime = true);

    /**
     * Đồng bộ sản phẩm từ shopee
     * @param int $storeId
     * @param array $shopeeItemIds
     * @return void
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function syncProduct($storeId, $shopeeItemIds = []);

    /**
     * lấy link intem của shopee
     * @param integer $shippingPartnerId
     * @param array $freightBillCodes
     * @return array|string
     * @throws RestApiException
     */
    public function downloadShippingDocument($shippingPartnerId, $freightBillCodes);

    /**
     * Tìm skus tương ứng từ variations bên shopee
     *
     * @param Store $store
     * @param array $models
     * @return Sku[]
     */
    public function findSkusByVariations(Store $store, array $models);

    /**
     * Lưu thông tin đối tác vận chuyển shopee
     *
     * @param int $tenantId
     * @param array $logistic
     * @return ShippingPartner|object
     */
    public function makeShippingPartner($tenantId, $logistic);

    /**
     * Cập nhật đối tác vận chuyển của shopee cho order
     *
     * @param Order $order
     * @param ShippingPartner $shippingPartner
     * @param User $creator
     * @return bool
     */
    public function updateOrderShippingPartner(Order $order, ShippingPartner $shippingPartner, User $creator);

    /**
     * Find shopee order
     *
     * @param int $storeId
     * @param string $code
     * @return Order|object|null
     */
    public function findOrder($storeId, $code);

    /**
     * Tạo thông tin token kết nối
     *
     * @param array $input
     * @return array
     */
    public function makeToken(array $input);

    /**
     * Make store connector
     *
     * @param Store $store
     * @return ShopeeStoreConnector
     */
    public function storeConnector(Store $store);

    /**
     * Detect api response error
     *
     * @param string $error
     * @param string $message
     * @return string|null
     */
    public function detectApiError($error, $message);

    /**
     * @param Store $store
     * @return array|mixed
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function getLogistic(Store $store);


    /**
     * @param Store $store
     * @param array $orderCodes
     * @return array|mixed
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function getOrderDetails(Store $store, $orderCodes);

    /**
     * @param Store $store
     * @param Sku $sku
     * @param integer $quantity
     * @param array $locationIds
     * @param array $params
     * @return array|mixed
     * @throws MarketplaceException
     * @throws RestApiException
     */
    public function updateStock(Store $store, Sku $sku, $quantity, $locationIds = [], &$params = []);

    /**
     * @param Store $store
     * @return array|mixed
     * @throws MarketplaceException
     * @throws RestApiException
     */
    public function getWarehouseDetail(Store $store);

    /**
     * @param $shippingCarrierName
     * @return array
     */
    public function makeLogisticDataFromName($shippingCarrierName);
}
