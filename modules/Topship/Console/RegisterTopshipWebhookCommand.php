<?php

namespace Modules\Topship\Console;

use Gobiz\Support\RestApiException;
use Illuminate\Console\Command;
use Modules\Topship\Services\TopshipApi;

class RegisterTopshipWebhookCommand extends Command
{
    protected $signature = 'topship:register-webhook {api_key} {webhook_url}';

    protected $description = 'Register topship webhook';

    /**
     * @throws RestApiException
     */
    public function handle()
    {
        $token = $this->argument('api_key');
        $webhookUrl = $this->argument('webhook_url');

        $api = new TopshipApi([
            'url' => config('services.topship.api_url'),
            'token' => $token,
        ]);

        $webhooks = $api->getWebhooks()->getData('webhooks');
        if (!empty($webhooks)) {
            $this->warn("Webhook is already registered");
            $this->line(json_encode($webhooks));
            return;
        }

        $res = $api->createWebhook([
            'url' => $webhookUrl.'?'.http_build_query(['key' => hash('sha256', $token)]),
            'entities' => ["fulfillment"],
            'fields' => [],
            'metadata' => '',
        ]);

        $this->info("Webhook registration successful");
        $this->line($res->getBody());
    }
}
