<?php

namespace Modules\KiotViet\Services;

use Gobiz\Support\RestApiException;
use Gobiz\Validation\ValidationException;
use Gobiz\Workflow\WorkflowException;
use Illuminate\Support\Arr;
use Modules\FreightBill\Models\FreightBill;
use Modules\Marketplace\Services\Marketplace;
use Modules\Order\Models\Order;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Store\Models\Store;
use Modules\User\Models\User;
use Gobiz\Log\LogService;
use Modules\KiotViet\Services\KiotVietServiceInterface;
use Modules\KiotViet\Commands\SyncKiotVietProducts;
use Modules\KiotViet\Commands\SyncKiotVietProduct;
use Modules\KiotViet\Commands\SyncKiotVietOrder;
use Modules\KiotViet\Commands\SyncKiotVietProductUpdate;
use Modules\KiotViet\Commands\SyncKiotVietFreightBill;
use Modules\KiotViet\Commands\SyncKiotVietOrders;

class KiotVietService implements KiotVietServiceInterface
{
    /**
     * @var KiotVietApiInterface
     */
    protected $api;

    protected $logger;

    /**
     * KiotVietService constructor
     *
     * @param KiotVietApiInterface $api
     */
    public function __construct(KiotVietApiInterface $api)
    {
        $this->api = $api;
        $this->logger = LogService::logger('kiotviet-sync-product', [
            'context' => ['aloha'],
        ]);
    }

    /**
     * Get KiotViet api
     *
     * @return KiotVietApiInterface
     */
    public function api()
    {
        return $this->api;
    }

    /**
     * Mapping trạng thái vận chuyển
     *
     * @param string $logisticsStatus
     * @return string|null
     */
    public function mapFreightBillStatus($logisticsStatus)
    {
        return Arr::get([
            KiotViet::LOGISTICS_PICKUP_DONE       => FreightBill::STATUS_CONFIRMED_PICKED_UP,
            KiotViet::LOGISTICS_PICKUP_RESTART    => FreightBill::STATUS_FAILED_PICK_UP,
            KiotViet::LOGISTICS_DELIVERY_DONE     => FreightBill::STATUS_DELIVERED,
            KiotViet::LOGISTICS_DELIVERY_CANCELED => FreightBill::STATUS_CANCELLED,
        ], $logisticsStatus);
    }

    /**
     * Đồng bộ đơn KiotViet theo danh sách order code
     *
     * @param Store $store
     * @return Order[]|null
     * @throws RestApiException
     * @throws WorkflowException
     */
    public function syncOrders(Store $store)
    {
        return (new SyncKiotVietOrders($store))->handle();
    }

    /**
     * Thực hiện đồng bộ đơn
     *
     * @param Store $store
     * @param array $orderInput Thông tin order theo response của KiotViet webhook
     * @return Order
     * @throws ValidationException
     */
    public function syncOrder(Store $store, array $orderInput)
    {
        return (new SyncKiotVietOrder($store, $orderInput))->handle();
    }

    /**
     * Đồng bộ toàn bộ sản phẩm từ KiotViet theo merchant
     * @param Store $store
     * @param boolean $filterUpdateTime
     * @return array|null
     * @throws RestApiException
     */
    public function syncProducts(Store $store, $filterUpdateTime = true)
    {
        return (new SyncKiotVietProducts($store, null, $filterUpdateTime))->handle();
    }

    /**
     * Đồng bộ sản phẩm từ KiotViet
     * @param Store $store
     * @param int $KiotVietItemId
     * @return Product
     * @throws RestApiException
     */
    public function syncProduct(Store $store, $KiotVietItemId)
    {
       return (new SyncKiotVietProduct($store, $KiotVietItemId))->handle();
    }

    /**
     * Thực hiện đồng bộ update thông tin đơn hàng
     *
     * @param Store $store
     * @param array $orderInput Thông tin order theo response của KiotViet webhook
     * @return Order
     * @throws ValidationException
     */
    public function syncProductUpdate(Store $store, array $orderInput)
    {
        return (new SyncKiotVietProductUpdate($store, $orderInput))->handle();
    }

    /**
     * lấy link intem của KiotViet
     * @param integer $shippingPartnerId
     * @param array $freightBillCodes
     * @return array|string
     */
    public function getAirwayBill($shippingPartnerId, $freightBillCodes)
    {

    }

    /**
     * Đồng bộ mã vận đơn KiotViet
     *
     * @param Store $store
     * @param Order $order
     * @param string $trackingNo
     * @return FreightBill[]
     * @throws RestApiException
     */
    public function syncFreightBill(Store $store, Order $order, string $trackingNo)
    {
        return (new SyncKiotVietFreightBill($store, $order, $trackingNo))->handle();
    }

    /**
     * Tìm skus tương ứng từ variations bên kiotviet
     * @param Store $store
     * @param array $variations
     * @return array|Sku[]
     * @throws RestApiException
     */
    public function findSkusByVariations(Store $store, array $variations)
    {
        $variationCodes  = [];
        foreach ($variations as $variation) {
            $variationCode    = $variation['productCode'];
            $variationCodes[] = $variationCode;
        }

        // Case sync sku tự động từ kiotviet
        $skus = Sku::query()
            ->where('merchant_id', $store->merchant_id)
            ->whereIn('code', $variationCodes)
            ->get();

        // Case map sku thủ công
        $storeSkus = $store->storeSkus()
            ->where('code', $variationCodes)
            ->with('sku')
            ->get();

        $results = [];
        foreach ($variations as $variation) {
            $variationCode  = $variation['productCode'];
            $variationId    = $variation['productId'];
            $sku = $skus->firstWhere('code', $variationCode);

            if ($sku instanceof Sku) {
                $results[$variationId] = $sku;
                continue;
            } else {
                // Tạo mới product
                $dataProduct = data_get((new SyncKiotVietProduct($store, $variationId))->handle(), 0);
                if ($dataProduct instanceof Product) {
                    $results[$variationId] = $dataProduct->skus->first();
                    continue;
                }

            }

            if ($storeSku = $storeSkus->firstWhere('code', $variationCode)) {
                $results[$variationId] = $storeSku->sku;
            }
        }

        return $results;
    }

    /**
     * Lưu thông tin đối tác vận chuyển KiotViet
     *
     * @param int $tenantId
     * @param array $logistic
     * @return ShippingPartner|object
     */
    public function makeShippingPartner($tenantId, $logistic)
    {
        $code = 'KIOTVIET'.$logistic['logistic_id'];
        return ShippingPartner::query()->firstOrCreate([
            'tenant_id' => $tenantId,
            'provider'  => ShippingPartner::PROVIDER_KIOTVIET,
            'code'      => $code,
        ], [
            'name'     => $logistic['logistic_name'],
            'settings' => Arr::only($logistic, ['logistic_id']),
            'alias' => [$code, strtolower($code)],
        ]);
    }

    /**
     * Cập nhật đối tác vận chuyển của KiotViet cho order
     *
     * @param Order $order
     * @param ShippingPartner $shippingPartner
     * @param User $creator
     */
    public function updateOrderShippingPartner(Order $order, ShippingPartner $shippingPartner, User $creator)
    {

    }

    /**
     * @param int $shopId
     * @param string $code
     * @return Order|null|object|void
     */
    public function findOrder($shopId, $code)
    {

    }

    /**
     * @param string $id
     * @param Store $store
     * @return array|mixed
     * @throws RestApiException
     */
    public function findInvoice(string $id, Store $store)
    {
        $response = $this->api->getInvoiceDetail($id, $store);
        return $response->getData();
    }

    /**
     * @param string $id
     * @param Store $store
     * @param $params
     * @return array|mixed
     * @throws RestApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateProduct(string $id, Store $store, $params)
    {
        $response = $this->api->updateProduct($id, $store, $params);
        return $response->getData();
    }

    /**
     * @param Store $store
     * @param array $params
     * @return array|mixed
     * @throws RestApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getBranches(Store $store, $params = [])
    {
        $response = $this->api->getBranches($store, $params);
        return $response->getData();
    }

    /**
     * @param Store $store
     * @return Store $store
     * @throws RestApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateToken(Store $store)
    {
        $clientId     = $store->getSetting('client_id');
        $clientSecret = $store->getSetting('secret_key');
        $shopName     = $store->getSetting('shop_name');

        $settings = $this->api->getSettingKiotViet($clientId, $clientSecret, $shopName);

        if ($settings) {
            $store->settings = $settings;
            $store->save();

        }

        return $store;
    }
}
