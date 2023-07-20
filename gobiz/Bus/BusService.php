<?php

namespace Gobiz\Bus;

use Illuminate\Pipeline\Pipeline;

class BusService
{
    const PIPELINE = 'Gobiz\Bus\Pipeline';

    /**
     * @return Pipeline
     */
    public static function pipeline()
    {
        return app(static::PIPELINE);
    }
}
