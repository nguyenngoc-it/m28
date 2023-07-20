<?php

namespace Gobiz\Kafka;

use Gobiz\Support\Manager;

class ConnectionManager extends Manager implements ConnectionManagerInterface
{
    /**
     * Get the storage key of driver
     *
     * @param string $driver
     * @return string
     */
    protected function makeStorageKey($driver)
    {
        return DispatcherInterface::class . '.' . $driver;
    }
}