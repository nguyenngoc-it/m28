<?php

namespace Modules\App\Services;

use Aws\Credentials\CredentialProvider;
use Aws\Sdk;
use Gobiz\Transformer\Commands\MakeTransformerManager;
use Gobiz\Transformer\TransformerManagerInterface;
use Gobiz\Transformer\TransformerService;

class AppService implements AppServiceInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    protected $response;

    /**
     * @var TransformerManagerInterface
     */
    protected $externalTransformer;

    /**
     * @var ResponseFactoryInterface
     */
    protected $externalResponse;

    /**
     * @var WebhookInterface
     */
    protected $webhook;

    /**
     * @var TransformerManagerInterface
     */
    protected $webhookTransformer;

    /**
     * @var Sdk
     */
    protected $aws;

    /**
     * Get response handler
     *
     * @return ResponseFactoryInterface
     */
    public function response()
    {
        return $this->response ?? $this->response = new ResponseFactory(TransformerService::transformers());
    }

    /**
     * Get external transformer instance
     *
     * @return TransformerManagerInterface
     */
    public function externalTransformer()
    {
        return $this->externalTransformer ?? $this->externalTransformer = (new MakeTransformerManager(
                config('api.external_transformers', []),
                config('api.external_transformer_finders', []),
            ))->handle();
    }

    /**
     * Get external response instance
     *
     * @return ResponseFactoryInterface
     */
    public function externalResponse()
    {
        return $this->externalResponse ?? $this->externalResponse = new ResponseFactory($this->externalTransformer());
    }

    /**
     * Get webhook instance
     *
     * @return WebhookInterface
     */
    public function webhook()
    {
        return $this->webhook ?? $this->webhook = new Webhook(config('webhook.api'));
    }

    /**
     * Get webhook transformer instance
     *
     * @return TransformerManagerInterface
     */
    public function webhookTransformer()
    {
        return $this->webhookTransformer ?? $this->webhookTransformer = (new MakeTransformerManager(
                config('webhook.transformers', []),
                config('webhook.transformer_finders', []),
            ))->handle();
    }

    /**
     * Get AWS SDK instance
     *
     * @return Sdk
     */
    public function aws()
    {
        return $this->aws ?? $this->aws = new Sdk([
                'region' => config('aws.region'),
                'version' => config('aws.version'),
                'credentials' => CredentialProvider::defaultProvider(),
            ]);
    }
}
