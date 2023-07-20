<?php

namespace Modules\EventBridge\Services;

use Aws\Result;
use Modules\Service;
use Throwable;

abstract class EventBridge
{
    /**
     * Get event name
     *
     * @return string
     */
    abstract public function getEventName();

    /**
     * Make event payload
     *
     * @return array
     */
    abstract public function getPayload();

    /**
     * Push event to queue
     */
    public function queue()
    {
        Service::eventBridge()->queue($this);
    }

    /**
     * Put event to event bridge
     *
     * @return Result
     * @throws Throwable
     */
    public function put()
    {
        return Service::eventBridge()->put($this);
    }
}
