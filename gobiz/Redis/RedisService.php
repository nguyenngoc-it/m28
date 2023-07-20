<?php

namespace Gobiz\Redis;

use Illuminate\Redis\RedisManager;

class RedisService
{
    /**
     * @return RedisManager
     */
    public static function redis()
    {
        return app('redis');
    }
}
