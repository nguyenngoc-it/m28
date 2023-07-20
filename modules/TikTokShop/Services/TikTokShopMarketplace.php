<?php

namespace Modules\TikTokShop\Services;

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

class TikTokShopMarketplace implements MarketplaceInterface, OAuth2Connectable
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Store Settings
     *
     * @var array $storeSettings
     */
    protected $storeSettings = [];

    /**
     * TikTokShopMarketplaceProvider constructor
     */
    public function __construct()
    {
        $this->logger = LogService::logger('tiktokshop');
    }

    /**
     * Mã nền tảng sản TMĐT
     *
     * @return string
     */
    public function getCode()
    {
        return Marketplace::CODE_TIKTOKSHOP;
    }

    /**
     * Tên nền tảng sản TMĐT
     *
     * @return string
     */
    public function getName()
    {
        return 'TikTokShop';
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
        $clientId  = config('services.tiktokshop.client_id');

        $query = http_build_query([
            'response_type' => 'code',
            'app_key'     => $clientId,
            'state'         => $state,
            'redirect_uri'  => $callbackUrl,
            // 'redirect_uri'  => 'https://api.m28.gobizdev.com/marketplaces/TikTokShop/oauth-callback',
        ]);

        return config('services.tiktokshop.authorization_url') . '?' . $query;
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

        if (!$code) {
            $this->logger->error('Authorization failed', $request->all());
            $response->error = "Authorization failed. Error: " . json_encode($request->only(['error', 'message']));

            return $response;
        }

        $storeSettings = $this->makeTokenConnection($code);

        $shopList  = data_get($storeSettings, 'seller_info.shop_list', []);
        $storeId   = '';
        $storeName = '';
        if (!empty($shopList)) {
            foreach ($shopList as $shop) {
                if (!$storeId) {
                    $storeId   = data_get($shop, 'shop_id');
                    $storeName = data_get($shop, 'shop_name');
                    break;
                }
            }
        }

        $response->storeId   = $storeId;
        $response->storeName = $storeName;
        $response->settings  = $storeSettings;

        return $response;
    }

    /**
     * Make Token Connection From Auth Code
     *
     * @param string $code
     * @return array
     */
    protected function makeTokenConnection(string $code)
    {
        // Make Seller Access Token
        $token = Service::tikTokShop()->api()->getAccessToken([
            'code' => $code
        ])->getData('data');

        $this->storeSettings = Service::tikTokShop()->makeToken($token);
        
        $accessToken = $this->storeSettings['access_token'];
        // $accessToken = "Sf6nn2d-rdn_3_55LMpwUX3mP64nNRrnwjY0YU_xrtE.n9Iij4JKQVAneSMcVblzZukMdtgl3Y0veLCgvL73guE";

        $this->makeTokenDataRelation($accessToken);

        return $this->storeSettings;
    }

    /**
     * Make Data Relationship For Token
     *
     * @param string $token
     * @return void
     */
    protected function makeTokenDataRelation(string $token)
    {
        $this->makeStoreInfo($token);
    }

    /**
     * Make Store Infomation
     *
     * @param string $token
     * @return void
     */
    protected function makeStoreInfo(string $token)
    {
        $paramRequests = [
            'access_token' => $token
        ];
        $sellerInfo = Service::tikTokShop()->api()->getSellerInfo($paramRequests)->getData('data');
        $this->storeSettings['seller_info'] = $sellerInfo;
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
        // return Service::tikTokShop()->storeConnector($store)->connect();
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
