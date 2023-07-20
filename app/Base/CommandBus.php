<?php

namespace App\Base;

use Illuminate\Support\Facades\Bus;

abstract class CommandBus
{
    /**
     * @see handle
     */
    public function dispatch()
    {
        return Bus::dispatch($this);
    }
}
