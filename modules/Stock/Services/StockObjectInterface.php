<?php

namespace Modules\Stock\Services;

interface StockObjectInterface
{
    /**
     * Get object type
     *
     * @return string
     */
    public function getObjectType();

    /**
     * Get object id
     *
     * @return string
     */
    public function getObjectId();
}