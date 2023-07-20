<?php
namespace Gobiz\Email;

use Closure;

interface EmailProviderManagerInterface
{
    /**
     * Register driver
     *
     * @param string $driver
     * @param object|Closure|string $instance
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
     * @return EmailProviderInterface
     */
    public function get($driver);

    /**
     * Get the registered driver list
     *
     * @return array
     */
    public function lists();
}