<?php

namespace Modules\Tenant\Services;

use Gobiz\Support\RestApiException;
use InvalidArgumentException;
use Modules\Tenant\Models\Tenant;
use Modules\Service;

class TenantWebhook implements TenantWebhookInterface
{
    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * TenantWebhook constructor
     *
     * @param Tenant $tenant
     */
    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    /**
     * Sync tenant to webhook service
     *
     * @return array
     * @throws RestApiException
     */
    public function syncTenant()
    {
        if ($app = $this->findTenant()) {
            return $app;
        }

        return Service::app()->webhook()
            ->createApplication([
                'code' => $this->tenant->code,
                'name' => $this->tenant->code,
            ])
            ->getData('application');
    }

    /**
     * @return array|null
     */
    protected function findTenant()
    {
        try {
            return Service::app()->webhook()->getApplication($this->tenant->code)->getData('application');
        } catch (RestApiException $exception) {
            return null;
        }
    }

    /**
     * Update webhook url
     *
     * @param string $url
     * @return array
     * @throws RestApiException
     */
    public function updateWebhookUrl($url)
    {
        if (!$this->tenant->webhook_id) {
            $this->syncTenant();
            $res = Service::app()->webhook()->createWebhook($this->tenant->code, ['url' => $url]);
        } else {
            $res = Service::app()->webhook()->updateWebhook($this->tenant->webhook_id, ['url' => $url]);
        }

        $this->saveWebhook($webhook = $res->getData('webhook'));

        return $webhook;
    }

    /**
     * Reset the webhook secret
     *
     * @return array
     * @throws RestApiException
     */
    public function resetWebhookSecret()
    {
        if (!$this->tenant->webhook_id) {
            throw new InvalidArgumentException("Webhook not found");
        }

        $res = Service::app()->webhook()->resetWebhookSecret($this->tenant->webhook_id);

        $this->saveWebhook($webhook = $res->getData('webhook'));

        return $webhook;
    }

    /**
     * @param array $webhook
     */
    protected function saveWebhook(array $webhook)
    {
        $this->tenant->update([
            'webhook_id' => $webhook['id'],
            'webhook_url' => $webhook['url'],
            'webhook_secret' => $webhook['secret'],
        ]);
    }

    /**
     * Delete webhook
     *
     * @throws RestApiException
     */
    public function deleteWebhook()
    {
        if ($webhookId = $this->tenant->webhook_id) {
            Service::app()->webhook()->deleteWebhook($webhookId);
        }

        $this->tenant->update([
            'webhook_id' => null,
            'webhook_url' => null,
            'webhook_secret' => null,
        ]);
    }

    /**
     * Init event
     *
     * @param string $event
     * @param array $payload
     * @param string|null $object
     * @param string|null $owner
     * @return WebhookEvent
     */
    public function event($event, array $payload = [], $object = null, $owner = null)
    {
        return new WebhookEvent($this->tenant, $event, $payload, $object, $owner);
    }
}
