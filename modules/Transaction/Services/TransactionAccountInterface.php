<?php

namespace Modules\Transaction\Services;

interface TransactionAccountInterface
{
    /**
     * @return int
     */
    public function getTenantId();

    /**
     * @return string
     */
    public function getAccountType();

    /**
     * @return string
     */
    public function getAccountId();
}
