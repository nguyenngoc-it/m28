<?php

namespace Gobiz\Kafka;

use Closure;

interface ConnectionManagerInterface
{
    /**
     * Register driver
     *
     * @param string $driver
     * @param DispatcherInterface|Closure|string $instance
     * @return static
     */
    public function register($driver, $instance);

    /**
     * Determine if the given driver has been registered
     *
     * @param string $driver
     * @return bool
     */
    public function has($driver);

    /**
     * Get driver instance
     *
     * @param string $driver
     * @return DispatcherInterface
     */
    public function get($driver);

    /**
     * Get the registered driver list
     *
     * @return array
     */
    public function lists();
}