<?php

namespace Gobiz\Support;

use Illuminate\Support\Arr;

abstract class ConstantCollection implements ConstantCollectionInterface
{
    /**
     * Get the constant label list
     *
     * @return array
     */
    public function labels()
    {
        return [];
    }

    /**
     * Return true if the constant code exists
     *
     * @param string $code
     * @return bool
     */
    public function has($code)
    {
        return in_array($code, $this->codes(), true);
    }

    /**
     * Get the constant data
     *
     * @param string $code
     * @return array|null
     */
    public function get($code)
    {
        if (!$this->has($code)) {
            return null;
        }

        return [
            'code' => $code,
            'label' => $this->label($code),
        ];
    }

    /**
     * Get the constant name
     *
     * @param string $code
     * @return string
     */
    public function label($code)
    {
        return Arr::get($this->labels(), (string)$code, $code);
    }

    /**
     * Get the list constant data
     *
     * @return array
     */
    public function lists()
    {
        return array_map(function ($code) {
            return $this->get($code);
        }, $this->codes());
    }
}