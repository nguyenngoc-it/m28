<?php

namespace Modules\Sapo\Services;

use Gobiz\Support\RestApiException;
use Gobiz\Validation\ValidationException;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Models\Order;
use Modules\Product\Models\Product;
use Modules\Store\Models\Store;

interface SapoServiceInterface
{
    /**
     * Get Sapo api
     *
     * @return SapoApiInterface
     */
    public function api();
    /**
     * Đồng bộ đơn Sapo theo danh sách order code
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
     * @param array $input Thông tin order theo response của Sapo webhook
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
     * Đồng bộ sản phẩm từ Sapo
     * @param Store $store
     * @param array $SapoItemId
     * @return array|Product[]
     * @throws RestApiException
     * @throws \Modules\Marketplace\Services\MarketplaceException
     */
    public function syncProduct(Store $store, $SapoItemId);

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
     * Đồng bộ mã vận đơn Sapo
     *
     * @param Store $store
     * @param Order $order
     * @param string $trackingNo
     * @return FreightBill[]
     * @throws RestApiException
     */
    public function syncFreightBill(Store $store, Order $order, string $trackingNo);
}
