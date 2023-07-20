<?php

namespace Modules\Tenant\Services;

use Gobiz\Support\RestApiException;

interface TenantWebhookInterface
{
    /**
     * Sync tenant to webhook service
     *
     * @return array
     * @throws RestApiException
     */
    public function syncTenant();

    /**
     * Update webhook url
     *
     * @param string $url
     * @return array
     * @throws RestApiException
     */
    public function updateWebhookUrl($url);

    /**
     * Reset the webhook secret
     *
     * @return array
     * @throws RestApiException
     */
    public function resetWebhookSecret();

    /**
     * Delete webhook
     *
     * @throws RestApiException
     */
    public function deleteWebhook();

    /**
     * Init event
     *
     * @param string $event
     * @param array $payload
     * @param string|null $object
     * @param string|null $owner
     * @return WebhookEvent
     */
    public function event($event, array $payload = [], $object = null, $owner = null);
}
