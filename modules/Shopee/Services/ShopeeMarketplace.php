<?php

namespace Modules\Shopee\Services;

use Carbon\Carbon;
use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Illuminate\Http\Request;
use Modules\Marketplace\Services\Marketplace;
use Modules\Marketplace\Services\MarketplaceException;
use Modules\Marketplace\Services\MarketplaceInterface;
use Modules\Marketplace\Services\OAuth2Connectable;
use Modules\Marketplace\Services\OAuth2Token;
use Modules\Marketplace\Services\OAuthResponse;
use Modules\Marketplace\Services\StoreConnectionInterface;
use Modules\Service;
use Modules\Store\Models\Store;
use Psr\Log\LoggerInterface;

class ShopeeMarketplace implements MarketplaceInterface, OAuth2Connectable
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * ShopeeMarketplaceProvider constructor
     */
    public function __construct()
    {
        $this->logger = LogService::logger('shopee');
    }

    /**
     * Mã nền tảng sản TMĐT
     *
     * @return string
     */
    public function getCode()
    {
        return Marketplace::CODE_SHOPEE;
    }

    /**
     * Tên nền tảng sản TMĐT
     *
     * @return string
     */
    public function getName()
    {
        return 'Shopee';
    }

    /**
     * Tạo authorization redirect url
     *
     * @param string $callbackUrl
     * @param string $state
     * @return string
     */
    public function makeOAuthUrl($callbackUrl, $state)
    {
        $partnerId = (int)config('services.shopee.partner_id');
        $partnerKey = config('services.shopee.partner_key');
        $time = time();

        $query = http_build_query([
            'partner_id' => $partnerId,
            'redirect' => $callbackUrl . '?' . http_build_query(['state' => $state]),
            'timestamp' => $time,
            'sign' => hash_hmac('sha256', $partnerId . '/api/v2/shop/auth_partner' . $time, $partnerKey),
        ]);

        return config('services.shopee.api_url') . '/api/v2/shop/auth_partner?' . $query;
    }

    /**
     * Xử lý sau khi authorization
     *
     * @param Request $request
     * @return OAuthResponse
     * @throws RestApiException
     */
    public function handleOAuthCallback(Request $request)
    {
        $response = new OAuthResponse();
        $response->state = $request->get('state');

        $code = (string)$request->get('code');
        $shopId = (int)$request->get('shop_id');

        if (!$code || !$shopId) {
            $this->logger->error('Authorization failed', $request->all());
            $response->error = "Authorization failed. Error: " . json_encode($request->only(['error', 'message']));

            return $response;
        }

        $token = Service::shopee()->publicApi()->getAccessToken([
            'code' => $code,
            'shop_id' => $shopId,
        ])->getData();

        // Get shop info
        $shopInfo = (new ShopeeShopApi($shopId, data_get($token, 'access_token')))->getShopInfo()->getData();
        $storeName = data_get($shopInfo, 'shop_name');

        $response->storeId   = $shopId;
        $response->storeName = $storeName;
        $response->settings = Service::shopee()->makeToken($token);

        return $response;
    }

    /**
     * Connect to store
     *
     * @param Store $store
     * @return StoreConnectionInterface
     * @throws MarketplaceException
     */
    public function connect(Store $store)
    {
        return Service::shopee()->storeConnector($store)->connect();
    }

    /**
     * Get oauth2 token
     *
     * @param Store $store
     * @return OAuth2Token
     */
    public function getOAuth2Token(Store $store)
    {
        $token = new OAuth2Token();
        $token->accessToken = $store->getSetting('access_token');
        $token->refreshToken = $store->getSetting('refresh_token');
        $token->accessTokenExpiredAt = ($time = (int)$store->getSetting('access_token_expired_at')) ? Carbon::createFromTimestamp($time) : null;
        $token->refreshTokenExpiredAt = ($time = (int)$store->getSetting('refresh_token_expired_at')) ? Carbon::createFromTimestamp($time) : null;

        return $token;
    }
}
