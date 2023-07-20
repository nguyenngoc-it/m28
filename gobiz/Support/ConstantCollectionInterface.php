<?php

namespace Gobiz\Support;

interface ConstantCollectionInterface
{
    /**
     * Return true if the constant code exists
     *
     * @param string $code
     * @return bool
     */
    public function has($code);

    /**
     * Get the constant data
     *
     * @param string $code
     * @return array|null
     */
    public function get($code);

    /**
     * Get the constant label
     *
     * @param string $code
     * @return string
     */
    public function label($code);

    /**
     * Get the list constant data
     *
     * @return array
     */
    public function lists();

    /**
     * Get the constant code list
     *
     * @return array
     */
    public function codes();
}