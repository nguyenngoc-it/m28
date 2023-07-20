<?php

namespace Modules\EventBridge\Services;

use Aws\Result;
use Gobiz\Transformer\TransformerManagerInterface;
use Throwable;

interface EventBridgeServiceInterface
{
    /**
     * Push event to queue for publish after
     *
     * @param EventBridge $event
     */
    public function queue(EventBridge $event);

    /**
     * Put event to event bridge
     *
     * @param EventBridge $event
     * @return Result
     * @throws Throwable
     */
    public function put(EventBridge $event);

    /**
     * Put events
     *
     * @param array $input
     * @return Result
     * @throws Throwable
     */
    public function putEvents($input);

    /**
     * Get transformer instance
     *
     * @return TransformerManagerInterface
     */
    public function getTransformer();
}
