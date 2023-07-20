<?php

namespace Modules\Gobiz\Services;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;
use Gobiz\Support\Traits\RestApiRequestTrait;
use GuzzleHttp\Client;
use Illuminate\Support\Str;

class M4Api implements M4ApiInterface
{
    use RestApiRequestTrait;

    /**
     * @var Client
     */
    protected $http;
    /**
     * @var string|null
     */
    protected $uniqueTrack;
    protected $headers;

    /**
     * @param $url
     * @param $tenantCode
     * @param array $options
     * @param string|null $uniqueTrack
     */
    public function __construct($url, $tenantCode, array $options = [], string $uniqueTrack = null)
    {
        $headers = [
            'X-Tenant' => $tenantCode,
            'Content-Type' => 'application/json',
        ];
        if ($this->uniqueTrack = $uniqueTrack) {
            $headers['Idempotency-Key'] = $uniqueTrack;
        }
        $this->headers = $headers;
        $this->http    = new Client(array_merge($options, [
            'base_uri' => $url,
            'headers' => $headers,
        ]));

        $this->logger = LogService::logger('m4-api');
    }

    /**
     * Get balance, credit of an Account
     *
     * @param string $account
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getAccount($account)
    {
        return $this->sendRequest(function () use ($account) {
            return $this->http->get('api/accounts/' . $account);
        });
    }

    /**
     * Get balance, credit of list Account
     *
     * @param array $filter
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getAccounts($filter = [])
    {
        return $this->sendRequest(function () use ($filter) {
            return $this->http->get('api/accounts', ['query' => $filter]);
        });
    }

    /**
     * Create Account
     *
     * @param array $payload
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function createAccount(array $payload)
    {
        return $this->sendRequest(function () use ($payload) {
            return $this->http->post('api/accounts', ['json' => $payload]);
        });
    }

    /**
     * List transaction of an account
     *
     * @param string $account
     * @param array $filter
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function transactions(string $account, array $filter = [])
    {
        return $this->sendRequest(function () use ($account, $filter) {
            return $this->http->get('api/accounts/' . $account . '/transactions', ['query' => $filter]);
        });
    }

    /**
     * Create refund transaction
     *
     * @param string $account
     * @param array $payload
     * @param string|null $uniqueTrack
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function refund(string $account, array $payload, string $uniqueTrack = null)
    {
        return $this->createTransaction('refund', $account, $payload, $uniqueTrack);
    }

    /**
     * Create collect transaction
     *
     * @param string $account
     * @param array $payload
     * @param string|null $uniqueTrack
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function collect(string $account, array $payload, string $uniqueTrack = null)
    {
        return $this->createTransaction('collect', $account, $payload, $uniqueTrack);
    }

    /**
     * Create deposit transaction
     *
     * @param string $account
     * @param array $payload
     * @param string|null $uniqueTrack
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function deposit(string $account, array $payload, string $uniqueTrack = null)
    {
        return $this->createTransaction('deposit', $account, $payload, $uniqueTrack);
    }

    /**
     * Create withdraw transaction
     *
     * @param string $account
     * @param array $payload
     * @param string|null $uniqueTrack
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function withdraw(string $account, array $payload, string $uniqueTrack = null)
    {
        return $this->createTransaction('withdraw', $account, $payload, $uniqueTrack);
    }

    /**
     * @param string $type
     * @param string $account
     * @param array $payload
     * @param string|null $uniqueTrack
     * @return RestApiResponse
     * @throws RestApiException
     */
    protected function createTransaction(string $type, string $account, array $payload, string $uniqueTrack = null)
    {
        $requestId = Str::uuid();
        $this->logger->info(strtoupper($type) . '.START', [
            'request_id' => $requestId,
            'account' => $account,
            'payload' => $payload,
            'unique_track' => $uniqueTrack
        ]);

        $response = $this->sendRequest(function () use ($type, $payload, $account, $uniqueTrack) {
            $endPoint = 'api/accounts/';
            $options  = ['json' => $payload];
            if ($uniqueTrack) {
                $endPoint           = 'api/internal/accounts/';
                $options['headers'] = array_merge($this->headers, ['Idempotency-Key' => $uniqueTrack]);
            }
            return $this->http->post($endPoint . $account . '/' . $type, $options);
        });

        $this->logger->info(strtoupper($type) . '.SUCCESS', [
            'request_id' => $requestId,
            'account' => $account,
            'response' => $response->getBody(),
        ]);

        return $response;
    }
}
