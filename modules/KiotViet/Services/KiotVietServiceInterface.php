<?php

namespace Modules\KiotViet\Services;

use Gobiz\Support\RestApiException;
use Gobiz\Validation\ValidationException;
use Gobiz\Workflow\WorkflowException;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Models\Order;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Store\Models\Store;
use Modules\User\Models\User;

interface KiotVietServiceInterface
{
    /**
     * Get KiotViet api
     *
     * @return KiotVietApiInterface
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
    * Đồng bộ đơn KiotViet theo danh sách order code
    *
    * @param Store $store
    * @return Order[]|null
    * @throws RestApiException
    * @throws WorkflowException
    */
   public function syncOrders(Store $store);

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
     * @param array $orderInput
     * @return Order
     */
    public function syncOrder(Store $store, array $orderInput);

    /**
     * Đồng bộ toàn bộ sản phẩm từ KiotViet theo merchant
     *
     * @param Store $store
     * @param boolean $filterUpdateTime
     * @return array|null
     */
    public function syncProducts(Store $store, $filterUpdateTime = true);

    /**
     * Đồng bộ sản phẩm từ KiotViet
     *
     * @param Store $store
     * @param int $KiotVietItemId
     * @return array|Product[]
     */
    public function syncProduct(Store $store, $KiotVietItemId);

    /**
     * Thực hiện đồng bộ update thông tin đơn hàng
     *
     * @param Store $store
     * @param array $orderInput Thông tin order theo response của KiotViet webhook
     * @return Order
     * @throws ValidationException
     */
    public function syncProductUpdate(Store $store, array $orderInput);


    /**
     * lấy link intem của KiotViet
     * @param integer $shippingPartnerId
     * @param array $freightBillCodes
     * @return array|string
     * @throws RestApiException
     */
    public function getAirwayBill($shippingPartnerId, $freightBillCodes);

    /**
     * Tìm skus tương ứng từ variations bên KiotViet
     *
     * @param Store $store
     * @param array $variations
     * @return Sku[]
     */
    public function findSkusByVariations(Store $store, array $variations);

    /**
     * Lưu thông tin đối tác vận chuyển KiotViet
     *
     * @param int $tenantId
     * @param array $logistic
     * @return ShippingPartner|object
     */
    public function makeShippingPartner($tenantId, $logistic);

    /**
     * Cập nhật đối tác vận chuyển của KiotViet cho order
     *
     * @param Order $order
     * @param ShippingPartner $shippingPartner
     * @param User $creator
     * @return bool
     */
    public function updateOrderShippingPartner(Order $order, ShippingPartner $shippingPartner, User $creator);

    /**
     * Find KiotViet order
     *
     * @param int $shopId
     * @param string $code
     * @return Order|object|null
     */
    public function findOrder($shopId, $code);

    /**
     * [findInvoice]
     * @param string $id [invoice Id]
     * @param Store $store
     * @return array|mixed
     */
    public function findInvoice(string $id, Store $store);

    /**
     * @param string $id
     * @param Store $store
     * @param $params
     * @return array|mixed
     * @throws RestApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateProduct(string $id, Store $store, $params);

    /**
     * get list Branches
     * @param Store $store
     * @param array $params
     * @throws RestApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getBranches(Store $store, $params = []);

    /**
     * @param Store $store
     * @return array|mixed
     * @throws RestApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateToken(Store $store);
}
