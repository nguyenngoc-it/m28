<?php

namespace Modules\TikTokShop\Services;

use Carbon\Carbon;
use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use Modules\FreightBill\Models\FreightBill;
use Modules\Marketplace\Services\MarketplaceException;
use Modules\Order\Models\Order;
use Modules\Product\Models\Product;
use Modules\TikTokShop\Commands\TiktokShopDownloadShippingDocument;
use Modules\Store\Models\Store;
use Modules\TikTokShop\Commands\SyncTikTokShopFreightBill;
use Modules\TikTokShop\Commands\SyncTikTokShopOrder;
use Modules\TikTokShop\Commands\SyncTikTokShopProduct;
use Modules\TikTokShop\Commands\SyncTikTokShopProducts;

class TikTokShopService implements TikTokShopServiceInterface
{
    /**
     * @var TikTokShopApiInterface
     */
    protected $api;

    /**
     * ShopeeService constructor
     *
     * @param TikTokShopApiInterface $api
     */
    public function __construct(TikTokShopApiInterface $api)
    {
        $this->api = $api;
    }

    /**
     * Get shopee api
     *
     * @return TikTokShopApiInterface
     */
    public function api()
    {
        return $this->api;
    }

    /**
     * Tạo thông tin token kết nối
     *
     * @param array $input
     * @return array
     */
    public function makeToken(array $input)
    {
        $accessToken = data_get($input,'access_token');
        $expiresIn   = (int) data_get($input,'access_token_expire_in');

        $refreshToken          = data_get($input,'refresh_token');
        $refreshTokenExpiresIn = (int) data_get($input,'refresh_token_expire_in');

        return [
            'access_token'            => $accessToken,
            'expire_at'               => time() + $expiresIn - (60*60),
            'refresh_token'           => $refreshToken,
            'refresh_token_expire_at' => time() + $refreshTokenExpiresIn - (60*60),
            'updated_at'              => Carbon::now()->toDateTimeString(),
        ];
    }

    /**
     * Đồng bộ đơn TikTokShop theo danh sách order code
     *
     * @param Store $store
     * @return Order[]|null
     * @throws RestApiException
     * @throws WorkflowException
     */
    public function syncOrders(Store $store)
    {
        // return (new SyncTikTokShopOrders($store))->handle();
    }

    /**
     * Thực hiện đồng bộ đơn
     *
     * @param Store $store
     * @param array $orderInput Thông tin order theo response của TikTokShop webhook
     * @return Order
     * @throws ValidationException
     */
    public function syncOrder(Store $store, array $orderInput)
    {
        return (new SyncTikTokShopOrder($store, $orderInput))->handle();
    }

    /**
     * Đồng bộ toàn bộ sản phẩm từ TikTokShop theo merchant
     * @param Store $store
     * @param int $merchantId
     * @param bool $filterUpdateTime
     * @return array|null
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function syncProducts(Store $store, $filterUpdateTime = true)
    {
        return (new SyncTikTokShopProducts($store, null, $filterUpdateTime))->handle();
    }

    /**
     * Đồng bộ sản phẩm từ TikTokShop
     * @param Store $store
     * @param array $TikTokShopItemId
     * @return array|Product[]
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function syncProduct(Store $store, $TikTokShopItemId)
    {
        return (new SyncTikTokShopProduct($store, $TikTokShopItemId))->handle();
    }

    /**
     * Đồng bộ mã vận đơn TikTokShop
     *
     * @param Store $store
     * @param Order $order
     * @param string $trackingNo
     * @return FreightBill[]
     * @throws RestApiException
     */
    public function syncFreightBill(Store $store, Order $order, string $trackingNo)
    {
        return (new SyncTikTokShopFreightBill($store, $order, $trackingNo))->handle();
    }

    /**
     * @param Store $store
     * @return Store $store
     * @throws RestApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function refreshToken(Store $store)
    {
        $params = [
            'refresh_token' => $store->getSetting('refresh_token')
        ];

        $token = $this->api->refreshToken($params)->getData('data');
        if ($token) {
            $settingToken = $this->makeToken($token);

            if ($settingToken) {
                $settingToken = array_merge($store->settings, $settingToken);
                $store->settings = $settingToken;
                $store->save();
            }
        } else {
            $store->status = Store::STATUS_DISCONNECTED;
            $store->save();
        }

        return $store;
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
            TikTokShop::LOGISTICS_TO_FULFILL => FreightBill::STATUS_WAIT_FOR_PICK_UP,
            TikTokShop::LOGISTICS_PROCESSING => FreightBill::STATUS_WAIT_FOR_PICK_UP,
            TikTokShop::LOGISTICS_FULFILLING => FreightBill::STATUS_DELIVERING,
            TikTokShop::LOGISTICS_COMPLETED  => FreightBill::STATUS_DELIVERED,
            TikTokShop::LOGISTICS_CANCELLED  => FreightBill::STATUS_CANCELLED,
        ], $logisticsStatus, null);
    }

    /**
     * lấy link intem của tiktok shop
     * @param int $shippingPartnerId
     * @param array $freightBillCodes
     * @return array|string
     */
    public function downloadShippingDocument($shippingPartnerId, $freightBillCodes)
    {
        return (new TiktokShopDownloadShippingDocument($shippingPartnerId, $freightBillCodes))->handle();
    }
}
