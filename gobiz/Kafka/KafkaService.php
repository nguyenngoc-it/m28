<?php

namespace Gobiz\Kafka;

class KafkaService
{
    /**
     * @return ConnectionManagerInterface
     */
    public static function connections()
    {
        return app(ConnectionManagerInterface::class);
    }

    /**
     * @param null|string $connection
     * @return DispatcherInterface
     */
    public static function dispatcher($connection = null)
    {
        return static::connections()->get($connection ?: config('kafka.default'));
    }
}