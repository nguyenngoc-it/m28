<?php

namespace Gobiz\Support;

use Closure;
use Illuminate\Contracts\Container\Container;

abstract class Manager
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var array
     */
    protected $drivers = [];

    /**
     * ServiceManager constructor
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Get the storage key of driver
     *
     * @param string $driver
     * @return string
     */
    abstract protected function makeStorageKey($driver);
    
    /**
     * Register driver
     *
     * @param string $driver
     * @param object|Closure|string $instance
     * @return static
     */
    public function register($driver, $instance)
    {
        if (is_object($instance) && !$instance instanceof Closure) {
            $this->container->instance($this->makeStorageKey($driver), $instance);
        } else {
            $this->container->singleton($this->makeStorageKey($driver), $instance);
        }

        $this->drivers[$driver] = true;

        return $this;
    }

    /**
     * Determine if the given driver has been registered
     *
     * @param string $driver
     * @return bool
     */
    public function has($driver)
    {
        return isset($this->drivers[$driver]);
    }

    /**
     * Get driver instance
     *
     * @param string $driver
     * @return object
     */
    public function get($driver)
    {
        return $this->container->make($this->makeStorageKey($driver));
    }

    /**
     * Get the registered driver list
     *
     * @return array
     */
    public function lists()
    {
        return array_keys($this->drivers);
    }
}