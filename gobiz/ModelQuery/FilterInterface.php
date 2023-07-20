<?php

namespace Gobiz\ModelQuery;

interface FilterInterface
{
    /**
     * Apply the filter
     *
     * @param ModelQuery $query
     * @param mixed $value
     */
    public function apply(ModelQuery $query, $value);
}