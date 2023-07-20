<?php

namespace Modules\Gobiz\Services;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiResponse;
use Gobiz\Support\Traits\RestApiRequestTrait;
use GuzzleHttp\Client;
use Modules\Tenant\Models\Tenant;

class M6Api implements M6ApiInterface
{
    use RestApiRequestTrait;

    /**
     * @var Client
     */
    protected $http;

    /**
     * GobizTranslate constructor
     *
     * @param string $url
     * @param string $token
     * @param array $options
     */
    public function __construct($url, $token, array $options = [])
    {
        $this->http = new Client(array_merge($options, [
            'base_uri' => $url,
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ],
        ]));

        $this->logger = LogService::logger('m6-api');
    }

    /**
     * Get authenticated user
     *
     * @return RestApiResponse
     */
    public function me()
    {
        return $this->request(function () {
            return $this->http->get('auth/user');
        });
    }

    /**
     * Create Package
     *
     * @param array $payload
     * @return RestApiResponse
     */
    public function createPackage(array $payload)
    {
        return $this->request(function () use ($payload) {
            return $this->http->post('/internal/packages/package-shipment', ['json' => $payload]);
        });
    }

    /**
     * @param Tenant $tenant
     * @param array $packageCodes
     * @return RestApiResponse
     */
    public function listPackage(Tenant $tenant, array $packageCodes)
    {
        return $this->request(function () use ($tenant, $packageCodes) {
            return $this->http->get('/internal/packages/tracking', [
                'query' => [
                    'tenant_code' => $tenant->getSetting(Tenant::SETTING_M6_AGENCY_CODE),
                    'packages' => $packageCodes
                ]
            ]);
        });
    }
}
