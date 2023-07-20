<?php

namespace Gobiz\Setting;

use Illuminate\Support\Collection;

interface SettingRepositoryInterface
{
    /**
     * Set the setting
     * Ex: set('foo', 'bar'), set(['foo' => 'bar', 'foo2' => 'bar2']);
     *
     * @param string|array $key
     * @param mixed $value
     */
    public function set($key, $value = null);

    /**
     * Get the setting
     * Ex: get('foo', 'defaultFoo'), get('foo*');
     *
     * @param string $key
     * @param mixed $default
     * @return Collection|mixed
     */
    public function get($key, $default = null);

    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function getValueByKey($key, $default = null);
}