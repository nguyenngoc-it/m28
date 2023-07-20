<?php

namespace Modules\ShopBase\Services;

use Gobiz\Support\RestApiResponse;

interface ShopBaseApiInterface
{
    /**
     * Create webhook
     *
     * @param array $payload
     * @return RestApiResponse
     */
    public function createWebhook(array $payload);

    /**
     * Delete webhook
     * @param $webhookId
     * @return RestApiResponse
     */
    public function deleteWebhook($webhookId);
}
