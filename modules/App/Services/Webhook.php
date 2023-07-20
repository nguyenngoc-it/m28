<?php

namespace Modules\App\Services;

use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;
use Gobiz\Support\Traits\RestApiRequestTrait;
use GuzzleHttp\Client;

class Webhook implements WebhookInterface
{
    use RestApiRequestTrait;

    /**
     * @var array
     */
    protected $config = [
        'url' => 'http://webhook/api/',
        'token' => null,
    ];

    /**
     * @var Client
     */
    protected $http;

    /**
     * WebhookApi constructor
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        $this->http = new Client([
            'base_uri' => rtrim($this->config['url'], '/').'/',
            'headers' => [
                'Authorization' => 'Bearer '.$this->config['token'],
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Get info of the current user
     *
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function me()
    {
        return $this->sendRequest(function () {
            return $this->http->get('me');
        });
    }

    /**
     * Create application
     *
     * @param array $data
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function createApplication(array $data)
    {
        return $this->sendRequest(function () use ($data) {
            return $this->http->post('applications', ['json' => $data]);
        });
    }

    /**
     * Get application detail
     *
     * @param string $code
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getApplication($code)
    {
        return $this->sendRequest(function () use ($code) {
            return $this->http->get("applications/{$code}");
        });
    }

    /**
     * Update application
     *
     * @param int $code
     * @param array $data
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function updateApplication($code, array $data)
    {
        return $this->sendRequest(function () use ($code, $data) {
            return $this->http->put("applications/{$code}", ['json' => $data]);
        });
    }

    /**
     * Publish event
     *
     * @param int $appCode
     * @param array $event
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function publishEvent($appCode, array $event)
    {
        return $this->sendRequest(function () use ($appCode, $event) {
            return $this->http->post("applications/{$appCode}/events", ['json' => $event]);
        });
    }

    /**
     * List application's webhooks
     *
     * @param string $appCode
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function listApplicationWebhooks($appCode)
    {
        return $this->sendRequest(function () use ($appCode) {
            return $this->http->get("applications/{$appCode}/webhooks");
        });
    }

    /**
     * Register new webhook for application
     *
     * @param int $appCode
     * @param array $webhook
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function createWebhook($appCode, array $webhook)
    {
        return $this->sendRequest(function () use ($appCode, $webhook) {
            return $this->http->post("applications/{$appCode}/webhooks", ['json' => $webhook]);
        });
    }

    /**
     * Update webhook
     *
     * @param int $webhookId
     * @param array $data
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function updateWebhook($webhookId, array $data)
    {
        return $this->sendRequest(function () use ($webhookId, $data) {
            return $this->http->put("webhooks/{$webhookId}", ['json' => $data]);
        });
    }

    /**
     * Reset webhook's secret
     *
     * @param int $webhookId
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function resetWebhookSecret($webhookId)
    {
        return $this->sendRequest(function () use ($webhookId) {
            return $this->http->put("webhooks/{$webhookId}/reset-secret");
        });
    }

    /**
     * Get webhook detail
     *
     * @param int $webhookId
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getWebhook($webhookId)
    {
        return $this->sendRequest(function () use ($webhookId) {
            return $this->http->get("webhooks/{$webhookId}");
        });
    }

    /**
     * Delete webhook
     *
     * @param int $webhookId
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function deleteWebhook($webhookId)
    {
        return $this->sendRequest(function () use ($webhookId) {
            return $this->http->delete("webhooks/{$webhookId}");
        });
    }
}
