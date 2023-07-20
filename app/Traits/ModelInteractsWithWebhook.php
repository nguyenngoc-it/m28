<?php

namespace App\Traits;

use App\Base\Model;
use Illuminate\Support\Str;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\WebhookEvent;

/**
 * Trait ModelInteractsWithWebhook
 *
 * @mixin Model
 */
trait ModelInteractsWithWebhook
{
    /**
     * Init webhoook event
     *
     * @param string $event
     * @param array $payload
     * @param string|null $owner
     * @return WebhookEvent
     */
    public function webhookEvent($event, array $payload = [], $owner = null)
    {
        /**
         * @var Tenant $tenant
         */
        $tenant = $this->getAttribute('tenant');

        return $tenant->webhook()->event($event, $payload, $this->getWebhookEventObject(), $owner);
    }

    /**
     * @return string
     */
    protected function getWebhookEventObject()
    {
        return strtoupper(Str::singular($this->getTable())) . '.' . $this->getKey();
    }
}
