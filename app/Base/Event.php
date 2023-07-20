<?php

namespace App\Base;

use Illuminate\Queue\SerializesModels;

abstract class Event
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
        dispatch(new EventJob($this));
    }
}
