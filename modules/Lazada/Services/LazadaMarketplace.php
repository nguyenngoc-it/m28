<?php

namespace Modules\Lazada\Services;

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

class LazadaMarketplace implements MarketplaceInterface, OAuth2Connectable
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * LazadaMarketplaceProvider constructor
     */
    public function __construct()
    {
        $this->logger = LogService::logger('lazada');
    }

    /**
     * Mã nền tảng sản TMĐT
     *
     * @return string
     */
    public function getCode()
    {
        return Marketplace::CODE_LAZADA;
    }

    /**
     * Tên nền tảng sản TMĐT
     *
     * @return string
     */
    public function getName()
    {
        return 'Lazada';
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
        $clientId = config('services.lazada.client_id');

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $callbackUrl . '?' . http_build_query(['state' => $state]),
        ]);

        return config('services.lazada.authorization_url') . '?' . $query;
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
        $response        = new OAuthResponse();
        $response->state = $request->get('state');
        $code            = (string)$request->get('code');
        if (!$code) {
            $this->logger->error('Authorization failed', $request->all());
            $response->error = "Authorization failed. Error: " . json_encode($request->only(['error', 'message']));

            return $response;
        }

        $token = Service::lazada()->api()->getAccessToken([
            'code' => $code
        ])->getData();

        $settings = Service::lazada()->makeToken($token);

        $shopId = data_get($settings, 'country_user_info.0.seller_id');

        $response->storeId  = $shopId;
        $response->settings = $settings;

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
        return Service::lazada()->storeConnector($store)->connect();
    }

    /**
     * Get oauth2 token
     *
     * @param Store $store
     * @return OAuth2Token
     */
    public function getOAuth2Token(Store $store)
    {
        $token                        = new OAuth2Token();
        $token->accessToken           = $store->getSetting('access_token');
        $token->refreshToken          = $store->getSetting('refresh_token');
        $token->accessTokenExpiredAt  = ($time = (int)$store->getSetting('access_token_expired_at')) ? Carbon::createFromTimestamp($time) : null;
        $token->refreshTokenExpiredAt = ($time = (int)$store->getSetting('refresh_token_expired_at')) ? Carbon::createFromTimestamp($time) : null;

        return $token;
    }
}
