<?php

namespace Modules\Transaction\Services;

class TransactionAccount implements TransactionAccountInterface
{
    /**
     * @var int
     */
    protected $tenantId;

    /**
     * @var string
     */
    protected $accountType;

    /**
     * @var string
     */
    protected $accountId;

    /**
     * TransactionAccount constructor
     *
     * @param int $tenantId
     * @param string $accountType
     * @param string $accountId
     */
    public function __construct($tenantId, $accountType, $accountId)
    {
        $this->tenantId = $tenantId;
        $this->accountType = $accountType;
        $this->accountId = $accountId;
    }

    /**
     * @return int
     */
    public function getTenantId()
    {
        return $this->tenantId;
    }

    /**
     * @return string
     */
    public function getAccountType()
    {
        return $this->accountType;
    }

    /**
     * @return string
     */
    public function getAccountId()
    {
        return $this->accountId;
    }
}
