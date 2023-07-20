<?php

namespace Modules\App\Services;

use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;

interface WebhookInterface
{
    /**
     * Get info of the current user
     *
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function me();

    /**
     * Create application
     *
     * @param array $data
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function createApplication(array $data);

    /**
     * Get application detail
     *
     * @param string $code
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getApplication($code);

    /**
     * Update application
     *
     * @param int $code
     * @param array $data
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function updateApplication($code, array $data);

    /**
     * Publish event
     *
     * @param int $appCode
     * @param array $event
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function publishEvent($appCode, array $event);

    /**
     * List application's webhooks
     *
     * @param string $appCode
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function listApplicationWebhooks($appCode);

    /**
     * Register new webhook for application
     *
     * @param int $appCode
     * @param array $webhook
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function createWebhook($appCode, array $webhook);

    /**
     * Update webhook
     *
     * @param int $webhookId
     * @param array $data
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function updateWebhook($webhookId, array $data);

    /**
     * Reset webhook's secret
     *
     * @param int $webhookId
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function resetWebhookSecret($webhookId);

    /**
     * Get webhook detail
     *
     * @param int $webhookId
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function getWebhook($webhookId);

    /**
     * Delete webhook
     *
     * @param int $webhookId
     * @return RestApiResponse
     * @throws RestApiException
     */
    public function deleteWebhook($webhookId);
}
