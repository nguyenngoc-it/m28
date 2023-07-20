<?php

namespace Modules\ShopBaseUs\Services;

use Gobiz\Support\RestApiException;
use Gobiz\Validation\ValidationException;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Models\Order;
use Modules\Product\Models\Product;
use Modules\Store\Models\Store;

interface ShopBaseUsServiceInterface
{
    /**
     * Get ShopBaseUs api
     *
     * @return ShopBaseUsApiInterface
     */
    public function api();
    /**
     * Đồng bộ đơn ShopBaseUs theo danh sách order code
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
     * @param array $input Thông tin order theo response của ShopBaseUs webhook
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
     * Đồng bộ sản phẩm từ ShopBaseUs
     * @param Store $store
     * @param array $ShopBaseUsItemId
     * @return array|Product[]
     * @throws RestApiException
     * @throws \Modules\Marketplace\Services\MarketplaceException
     */
    public function syncProduct(Store $store, $ShopBaseUsItemId);

    /**
     * Tạo thông tin token kết nối
     *
     * @param array $input
     * @return array
     */
    public function makeToken(array $input);

    /**
     * Connect Shopbase Store
     *
     * @param array $input
     * @return array
     */
    public function connect(array $input);

    /**
     * Mapping trạng thái vận chuyển
     *
     * @param string $logisticsStatus
     * @return string|null
     */
    public function mapFreightBillStatus($logisticsStatus);

   /**
     * Đồng bộ mã vận đơn ShopBaseUs
     *
     * @param Store $store
     * @param Order $order
     * @param string $trackingNo
     * @return FreightBill[]
     * @throws RestApiException
     */
    public function syncFreightBill(Store $store, Order $order, string $trackingNo);
}
