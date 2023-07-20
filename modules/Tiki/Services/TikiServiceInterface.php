<?php

namespace Modules\Tiki\Services;

use Gobiz\Support\RestApiException;
use Gobiz\Validation\ValidationException;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Models\Order;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Store\Models\Store;
use Modules\User\Models\User;

interface TikiServiceInterface
{
    /**
     * Get Tiki api
     *
     * @return TikiApiInterface
     */
    public function api();
    /**
     * Đồng bộ đơn Tiki theo danh sách order code
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
     * @param array $input Thông tin order theo response của Tiki webhook
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
     * Đồng bộ sản phẩm từ Tiki
     * @param Store $store
     * @param array $TikiItemId
     * @return array|Product[]
     * @throws RestApiException
     * @throws \Modules\Marketplace\Services\MarketplaceException
     */
    public function syncProduct(Store $store, $TikiItemId);

    /**
     * Tạo thông tin token kết nối
     *
     * @param array $input
     * @return array
     */
    public function makeToken(array $input);

    /**
     * @param Store $store
     * @return Store $store
     * @throws RestApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function refreshToken(Store $store);

    /**
     * Get queue sub từ Tiki
     * @param Store $store
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function syncQueueSubscription(Store $store);
}
