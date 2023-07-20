<?php

namespace Modules\Product\Events;

use Illuminate\Queue\SerializesModels;
use Modules\Product\Jobs\SkuEventJob;

abstract class SkuEvent
{
    use SerializesModels;

    /**
     * Dispatch event
     */
    public function dispatch()
    {
        event($this);
    }

    /**
     * Push event to queue
     */
    public function queue()
    {
        dispatch(new SkuEventJob($this));
    }
}
