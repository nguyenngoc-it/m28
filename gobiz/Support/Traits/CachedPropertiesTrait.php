<?php

namespace Gobiz\Support\Traits;

use Closure;

trait CachedPropertiesTrait
{
    /**
     * The cached attributes data
     *
     * @var array
     */
    protected $cachedProperties = [];

    /**
     * Get model state
     *
     * @param string $name
     * @param Closure $getter
     * @return mixed
     */
    protected function getCachedProperty($name, Closure $getter)
    {
        if (!array_key_exists($name, $this->cachedProperties)) {
            $this->cachedProperties[$name] = $getter();
        }

        return $this->cachedProperties[$name];
    }

    /**
     * Unset model state
     *
     * @param string $name
     */
    protected function unsetCachedProperty($name)
    {
        unset($this->cachedProperties[$name]);
    }
}