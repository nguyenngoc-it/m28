<?php

namespace Gobiz\Setting;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SettingRepository implements SettingRepositoryInterface
{
    /**
     * Set the setting
     * Ex: set('foo', 'bar'), set(['foo' => 'bar', 'foo2' => 'bar2']);
     *
     * @param string|array $key
     * @param mixed $value
     */
    public function set($key, $value = null)
    {
        $setting = is_array($key) ? $key : [$key => $value];

        foreach ($setting as $key => $value) {
            Setting::updateOrInsert(compact('key'), compact('value'));
        }
    }

    /**
     * Get the setting
     * Ex: get('foo', 'defaultFoo'), get('foo*');
     *
     * @param string $key
     * @param mixed $default
     * @return Collection|mixed
     */
    public function get($key, $default = null)
    {
        return (is_string($key) && Str::endsWith($key, '*'))
            ? $this->getSettingByPrefix(rtrim($key, '*'))
            : $this->getSettingByKey($key, $default);
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function getValueByKey($key, $default = null)
    {
        $setting = Setting::query()->where('key', $key )
            ->get()->first();
        return (isset($setting->value)) ? $setting->value : $default;
    }

    /**
     * @param string $prefix
     * @return Collection
     */
    protected function getSettingByPrefix($prefix)
    {
        return Setting::query()->where('key', 'like', $prefix . '%')
            ->get()
            ->pluck('value', 'key');
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return Collection|null
     */
    protected function getSettingByKey($key, $default = null)
    {
        $settings = Setting::query()->whereIn('key', (array)$key)->get();

        if (is_array($key)) {
            return $settings->pluck('value', 'key');
        }

        return ($setting = $settings->first()) ? $setting->value : $default;
    }
}