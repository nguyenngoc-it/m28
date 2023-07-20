<?php

namespace Modules\Tiki\Services;

use Carbon\Carbon;
use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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

class TikiMarketplace implements MarketplaceInterface, OAuth2Connectable
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
     * TikiMarketplaceProvider constructor
     */
    public function __construct()
    {
        $this->logger = LogService::logger('tiki');
    }

    /**
     * Mã nền tảng sản TMĐT
     *
     * @return string
     */
    public function getCode()
    {
        return Marketplace::CODE_TIKI;
    }

    /**
     * Tên nền tảng sản TMĐT
     *
     * @return string
     */
    public function getName()
    {
        return 'tiki';
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
        $clientId  = config('services.tiki.client_id');

        $query = http_build_query([
            'response_type' => 'code',
            'client_id'     => $clientId,
            'state'         => $state,
            'scope'         => 'order product',
            'redirect_uri'  => $callbackUrl,
            // 'redirect_uri'  => 'https://api.m28.gobizdev.com/marketplaces/TIKI/oauth-callback',
        ]);

        return config('services.tiki.authorization_url') . '?' . $query;
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

        $response->storeId   = data_get($storeSettings, 'seller_info.id');
        $response->storeName = data_get($storeSettings, 'seller_info.name');
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
        $token = Service::tiki()->api()->getAccessToken([
            'code' => $code
        ])->getData();

        $this->storeSettings = Service::tiki()->makeToken($token);
        
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
        $this->makeQueueFlow($token);
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
        $sellerInfo = Service::tiki()->api()->getSellerInfo($paramRequests)->getData();
        $this->storeSettings['seller_info'] = $sellerInfo;
    }

    /**
     * Make Queue Workflow
     * 
     * https://open.tiki.vn/docs/docs/current/api-references/event-queue-api/
     *
     * @param string $token
     * @return void
     */
    protected function makeQueueFlow(string $token)
    {
        // Create Token Client Credentials
        $cientCredential = Service::tiki()->api()->makeClientCredentials([])->getData();
        $tokenClientCredential = data_get($cientCredential, 'access_token');
        if ($tokenClientCredential) {
            //Create Queue
            $paramRequests = [
                'access_token' => $tokenClientCredential,
                'name'         => 'Tiki Queue'
            ];
            $queue = Service::tiki()->api()->createQueue($paramRequests)->getData();
            $this->storeSettings['token_client_credential'] = $tokenClientCredential;
            $this->storeSettings['queue_code']              = data_get($queue, 'code');

            $this->storeSettings['subscription'] = [];

            // Create Subscription Order Created
            $this->makeSubscriptionOrderCreated($token);

            // Create Subscription Order Update Status
            $this->makeSubscriptionOrderUpdateStatus($token);
        }
    }

    /**
     * Make Subscription Order Created
     *
     * @param string $token
     * @return void
     */
    protected function makeSubscriptionOrderCreated(string $token)
    {
        $paramRequests = [
            'event_type'   => 'ORDER_CREATED_SUCCESSFULLY',
            'access_token' => $token,
            'queue_code'   => $this->storeSettings['queue_code']
        ];
        $subscriptionOrderCreated = Service::tiki()->api()->createSubscription($paramRequests)->getData();
        if ($subscriptionOrderCreated) {
            array_push($this->storeSettings['subscription'], $subscriptionOrderCreated);
        }
    }

     /**
     * Make Subscription Order Update Status
     *
     * @param string $token
     * @return void
     */
    protected function makeSubscriptionOrderUpdateStatus(string $token)
    {
        $paramRequests = [
            'event_type'   => 'ORDER_STATUS_UPDATED',
            'access_token' => $token,
            'queue_code'   => $this->storeSettings['queue_code']
        ];
        $subscriptionOrderStatus = Service::tiki()->api()->createSubscription($paramRequests)->getData();
        if ($subscriptionOrderStatus) {
            array_push($this->storeSettings['subscription'], $subscriptionOrderStatus);
        }
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
        // return Service::tiki()->storeConnector($store)->connect();
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
