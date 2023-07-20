<?php

namespace Modules\App\Services;

use Aws\Sdk;
use Gobiz\Transformer\TransformerManagerInterface;

interface AppServiceInterface
{
    /**
     * Get response handler
     *
     * @return ResponseFactoryInterface
     */
    public function response();

    /**
     * Get webhook instance
     *
     * @return WebhookInterface
     */
    public function webhook();

    /**
     * Get external transformer instance
     *
     * @return TransformerManagerInterface
     */
    public function externalTransformer();

    /**
     * Get external response instance
     *
     * @return ResponseFactoryInterface
     */
    public function externalResponse();

    /**
     * Get webhook transformer instance
     *
     * @return TransformerManagerInterface
     */
    public function webhookTransformer();

    /**
     * Get AWS SDK instance
     *
     * @return Sdk
     */
    public function aws();
}
