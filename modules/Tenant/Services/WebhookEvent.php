<?php

namespace Modules\Tenant\Services;

use Gobiz\Support\RestApiException;
use Modules\Service;
use Modules\Tenant\Jobs\PublishWebhookEventJob;
use Modules\Tenant\Models\Tenant;

class WebhookEvent
{
    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var string
     */
    protected $event;

    /**
     * @var array
     */
    protected $payload = [];

    /**
     * @var string|null
     */
    protected $object = null;

    /**
     * @var string|null
     */
    protected $owner = null;

    /**
     * WebhookEvent constructor
     *
     * @param Tenant $tenant
     * @param string $event
     * @param array $payload
     * @param string|null $object
     * @param string|null $owner
     */
    public function __construct(Tenant $tenant, $event, array $payload = [], $object = null, $owner = null)
    {
        $this->tenant = $tenant;
        $this->event = $event;
        $this->payload = $payload;
        $this->object = $object;
        $this->owner = $owner;
    }

    /**
     * Publish event
     *
     * @throws RestApiException
     */
    public function publish()
    {
        if (!$this->shouldPublish()) {
            return null;
        }

        return Service::app()->webhook()->publishEvent($this->tenant->code, $this->makeEventData());
    }

    /**
     * Create job to publish event
     */
    public function queue()
    {
        if (!$this->shouldPublish()) {
            return;
        }

        dispatch(new PublishWebhookEventJob($this->tenant->code, $this->makeEventData()));
    }

    /**
     * @return array
     */
    protected function makeEventData()
    {
        return array_filter([
            'name' => $this->event,
            'payload' => Service::app()->webhookTransformer()->transform($this->payload),
            'object' => $this->object,
            'owner' => $this->owner,
        ]);
    }

    /**
     * @return bool
     */
    protected function shouldPublish()
    {
        return !!(int)$this->tenant->getSetting(Tenant::SETTING_WEBHOOK_PUBLISH_EVENT);
    }

    /**
     * @return Tenant
     */
    public function getTenant()
    {
        return $this->tenant;
    }

    /**
     * @return string
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @return string|null
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @return string|null
     */
    public function getOwner()
    {
        return $this->owner;
    }
}
