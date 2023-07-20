<?php

namespace Gobiz\Log;

use Psr\Log\LoggerInterface;

class LogService
{
    /**
     * Get the logger factory implementation
     *
     * @return LoggerFactoryInterface
     */
    public static function loggers()
    {
        return app(LoggerFactoryInterface::class);
    }

    /**
     * Make the new logger
     *
     * @param string $name
     * @param array $options
     * @return LoggerInterface
     */
    public static function logger($name, array $options = [])
    {
        return static::loggers()->make($name, $options);
    }
}