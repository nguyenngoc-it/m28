<?php

namespace Modules\Transaction\Services;

interface MerchantTransObjInterface
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
