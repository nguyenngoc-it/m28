<?php

namespace Modules\Lazada\Services;

use Gobiz\Support\RestApiException;
use Gobiz\Validation\ValidationException;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Models\Order;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Store\Models\Store;
use Modules\User\Models\User;

interface LazadaServiceInterface
{
    /**
     * Get lazada api
     *
     * @return LazadaApiInterface
     */
    public function api();

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
     * Đồng bộ mã vận đơn KiotViet
     *
     * @param Store $store
     * @param Order $order
     * @param string $trackingNo
     * @return FreightBill[]
     * @throws RestApiException
     */
    public function syncFreightBill(Store $store, Order $order, string $trackingNo);

    /**
     * Thực hiện đồng bộ đơn
     *
     * @param Store $store
     * @param array $input Thông tin order theo response của Lazada webhook
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
     * Đồng bộ sản phẩm từ shopee
     * @param int $storeId
     * @param array $lazadaItemId
     * @return array|Product[]
     * @throws RestApiException
     * @throws \Modules\Marketplace\Services\MarketplaceException
     */
    public function syncProduct(Store $store, $lazadaItemId);

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
     * @throws \Modules\Marketplace\Services\MarketplaceException
     */
    public function getLogistic(Store $store);


    /**
     * @param Store $store
     * @param array $orderCodes
     * @return array|mixed
     * @throws RestApiException
     * @throws \Modules\Marketplace\Services\MarketplaceException
     */
    public function getOrderDetails(Store $store, $orderCodes);

    /**
     * @param Store $store
     * @param $merchantId
     * @param bool $filterUpdateTime
     * @param $type
     * @return mixed
     */
    public function syncStockSkus(Store $store,$merchantId, $filterUpdateTime = true, $type);

    /**
     * @param Sku $sku
     * @param Store $store
     * @return mixed
     */
    public function syncStockSku(Sku $sku,Store $store);
}
