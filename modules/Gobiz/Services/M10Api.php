<?php

namespace Modules\Gobiz\Services;

use Gobiz\Log\LogService;
use Gobiz\Support\Traits\RestApiRequestTrait;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Modules\Tenant\Models\Tenant;

class M10Api implements M10ApiInterface
{
    use RestApiRequestTrait;

    /**
     * The HTTP Client instance.
     *
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var string
     */
    protected $baseUri = '';

    /**
     * GobizTranslate constructor
     * @param Tenant $tenant
     * @param string $baseUri
     * @param array $options
     */
    public function __construct(Tenant $tenant, $baseUri, array $options = [])
    {
        $this->tenant  = $tenant;
        $this->baseUri = $baseUri;
        $this->options = $options;
        $this->logger  = LogService::logger('m10-api');
    }


    /**
     * Get a instance of the Guzzle HTTP client.
     *
     * @param array $headers
     * @return \GuzzleHttp\Client
     */
    protected function getHttpClient($headers = [])
    {
        return new Client(array_merge($this->options, [
            'base_uri' => $this->baseUri,
            'headers' => $headers,
        ]));
    }

    /**
     * Get the access token response for the given code.
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAccessTokenResponse()
    {
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
        return $this->getHttpClient($headers)->post('oauth/token', [
            'form_params' => $this->getTokenFields(),
        ]);
    }

    /**
     * Get the POST fields for the token request.
     *
     * @return array
     */
    protected function getTokenFields()
    {
        $fields = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->tenant->client_id,
            'client_secret' => $this->tenant->client_secret,
        ];

        return $fields;
    }


    /**
     * @param array $input
     * @return \Gobiz\Support\RestApiResponse|mixed|void
     */
    public function register(array $input)
    {
        $response = $this->request(function () {
            return $this->getAccessTokenResponse();
        });

        $token = $response->getData('access_token');
        if(empty($token)) {
            $this->logger->info('get token error ', [
                    'response' => $response->getBody()
                ]
            );
            return;
        }

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$token
        ];
        return $this->request(function () use($input, $headers) {
            return $this->getHttpClient($headers)->post('internal/users/register', [
                'json' => $input
            ]);
        });
    }
}
