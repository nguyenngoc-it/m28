<?php

namespace Gobiz\Support;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class OptionsAccess implements Arrayable
{
    /**
     * Params constant
     */
    const PARAM_REQUIRED = 'required';
    const PARAM_DEFAULT = 'default';
    const PARAM_ALLOWED_TYPES = 'allowed_types';
    const PARAM_ALLOWED_VALUES = 'allowed_values';
    const PARAM_INSTANCEOF = 'instanceof';
    const PARAM_NORMALIZER = 'normalizer';

    /**
     * Options config
     *
     *    $config[option] => [
     *        'required' => true,
     *        'default' => 'default value',
     *        'allowed_types' => ['array'], // is_<type>() function is defined in PHP
     *        'allowed_values' => ['value'],
     *        'instanceof' => 'ClassName',
     *        'normalizer' => function($value, $options) {
     *              return $value;
     *        },
     *    ]
     *
     * @var array
     */
    protected $config = [];

    /**
     * Options data
     *
     * @var array
     */
    protected $options = [];

    /**
     * Make the options config
     *
     * @return array
     */
    abstract protected function makeConfig();

    /**
     * OptionsAccess constructor
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->config = $this->handleConfig($this->makeConfig());
        $this->setOptions($options);
    }

    /**
     * Handle the option config
     *
     * @param array $config
     * @return array
     */
    protected function handleConfig(array $config)
    {
        return array_map(function ($config) {
            if (empty($config[static::PARAM_REQUIRED]) && !array_key_exists(static::PARAM_DEFAULT, $config)) {
                $config[static::PARAM_DEFAULT] = null;
            }

            if (!empty($config[static::PARAM_REQUIRED])) {
                unset($config[static::PARAM_DEFAULT]);
            }

            return $config;
        }, $config);
    }

    /**
     * Set options
     *
     * @param array $options
     */
    protected function setOptions(array $options)
    {
        $this->options = $this->resolveOptions($options);
    }

    /**
     * Resolve the given options with current config
     *
     * @param array $options
     * @return array
     */
    protected function resolveOptions(array $options)
    {
        // Only accept the configured options
        $options = Arr::only($options, array_keys($this->config));

        $resolver = new OptionsResolver();

        $this->configureOptions($resolver);

        return $resolver->resolve($options);
    }

    /**
     * Configure the options
     *
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(array_keys(array_filter($this->listConfig(static::PARAM_REQUIRED))))
            ->setDefaults($this->listConfig(static::PARAM_DEFAULT));

        foreach ($this->listConfig(static::PARAM_ALLOWED_TYPES) as $option => $allowedTypes) {
            $resolver->setAllowedTypes($option, $allowedTypes);
        }

        foreach ($this->listConfig(static::PARAM_ALLOWED_VALUES) as $option => $allowedValues) {
            $resolver->setAllowedValues($option, $allowedValues);
        }

        foreach ($this->listConfig(static::PARAM_INSTANCEOF) as $option => $class) {
            $resolver->addAllowedValues($option, function ($value) use ($class) {
                return $value instanceof $class;
            });
        }

        foreach ($this->listConfig(static::PARAM_NORMALIZER) as $option => $normalizer) {
            $resolver->setNormalizer($option, function ($options, $value) use ($normalizer) {
                $normalizer = is_string($normalizer) ? $this->makeNormalizer($normalizer) : $normalizer;

                return call_user_func($normalizer, $value, $options);
            });
        }
    }

    /**
     * Make normalizer by type
     *
     * @param string $type
     * @return Closure
     */
    protected function makeNormalizer($type)
    {
        return function ($value) use ($type) {
            if (is_null($value)) {
                return $value;
            }

            switch ($type) {
                case 'int':
                case 'integer':
                    return (int)$value;
                case 'real':
                case 'float':
                case 'double':
                    return (float)$value;
                case 'string':
                    return (string)$value;
                case 'bool':
                case 'boolean':
                    return (bool)$value;
                case 'date':
                    return (new DateTime($value))->startOfDay();
                case 'datetime':
                    return new DateTime($value);
                case 'timestamp':
                    return (new DateTime($value))->getTimestamp();
                default:
                    return $value;
            }
        };
    }

    /**
     * Get the config of a param of all options
     *
     * @param string $param
     * @return array
     */
    protected function listConfig($param)
    {
        $result = [];

        foreach (array_keys($this->config) as $option) {
            if ($this->hasOptionConfig($option, $param)) {
                $result[$option] = $this->getOptionConfig($option, $param);
            }
        }

        return $result;
    }

    /**
     * Determine if given param exists in option config
     *
     * @param string $option
     * @param string $param
     * @return bool
     */
    protected function hasOptionConfig($option, $param)
    {
        $config = Arr::get($this->config, (string)$option, []);

        return array_key_exists($param, $config);
    }

    /**
     * Get the config of option
     *
     * @param string $option
     * @param string $param
     * @param mixed $default
     * @return mixed
     */
    protected function getOptionConfig($option, $param = null, $default = null)
    {
        $config = Arr::get($this->config, (string)$option, []);

        return Arr::get($config, $param, $default);
    }

    /**
     * Get the value of the given option
     *
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return Arr::get($this->options, (string)$key, $default);
    }

    /**
     * Get the given options
     *
     * @return array
     */
    public function all()
    {
        $keys = array_merge(array_keys($this->config), array_keys($this->options));

        return $this->only(array_unique($keys));
    }

    /**
     * Get a subset of the items from the given options
     *
     * @param string|array $keys
     * @return array
     */
    public function only($keys)
    {
        $result = [];

        foreach ((array)$keys as $key) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    /**
     * Get all of the given options except for a specified array of items.
     *
     * @param string|array $keys
     * @return array
     */
    public function except($keys)
    {
        return Arr::except($this->all(), $keys);
    }

    /**
     * Getter
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Isset
     *
     * @param string $key
     * @return bool
     */
    public function __isset($key)
    {
        return !is_null($this->get($key));
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->all();
    }
}