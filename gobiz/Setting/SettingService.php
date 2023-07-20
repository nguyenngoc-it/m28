<?php

namespace Gobiz\Setting;

use Illuminate\Support\Collection;

class SettingService
{
    /**
     * @return SettingRepositoryInterface
     */
    public static function repository()
    {
        return app(SettingRepositoryInterface::class);
    }

    /**
     * Set the setting
     * Ex: set('foo', 'bar'), set(['foo' => 'bar', 'foo2' => 'bar2']);
     *
     * @param string|array $key
     * @param mixed $value
     */
    public static function set($key, $value = null)
    {
        return static::repository()->set($key, $value);
    }

    /**
     * Get the setting
     * Ex: get('foo', 'defaultFoo'), get('foo*');
     *
     * @param string $key
     * @param mixed $default
     * @return Collection|mixed
     */
    public static function get($key, $default = null)
    {
        return static::repository()->get($key, $default);
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    public static function getValueByKey($key, $default = null)
    {
        return static::repository()->getValueByKey($key, $default);
    }
}