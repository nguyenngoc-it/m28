<?php

namespace Modules\Transaction\Services;

use Gobiz\Support\RestApiException;
use Modules\Gobiz\Services\M4ApiInterface;
use Modules\Service;
use Modules\Transaction\Models\Transaction;
use Modules\User\Models\User;

class Wallet
{
    /**
     * @var M4ApiInterface
     */
    protected $m4Api;

    /**
     * @var string
     */
    protected $account;

    /**
     * @var TransactionAccountInterface
     */
    protected $transactionAccount;

    /**
     * Wallet constructor
     *
     * @param M4ApiInterface $m4Api
     * @param string $account
     * @param TransactionAccountInterface $transactionAccount
     */
    public function __construct(M4ApiInterface $m4Api, string $account, TransactionAccountInterface $transactionAccount)
    {
        $this->m4Api = $m4Api;
        $this->account = $account;
        $this->transactionAccount = $transactionAccount;
    }

    /**
     * Get wallet account
     *
     * @return string
     */
    public function account()
    {
        return $this->account;
    }

    /**
     * Create wallet
     *
     * @param array $payload
     * @return array
     * @throws RestApiException
     */
    public function create(array $payload = [])
    {
        $payload['account'] = $this->account;

        return $this->m4Api->createAccount($payload)->getData();
    }

    /**
     * Get wallet detail
     *
     * @return array
     * @throws RestApiException
     */
    public function detail()
    {
        return $this->m4Api->getAccount($this->account)->getData();
    }

    /**
     * Get waller transactions
     *
     * @param array $filter
     * @return array
     * @throws RestApiException
     */
    public function transactions(array $filter = [])
    {
        return $this->m4Api->transactions($this->account, $filter)->getData();
    }

    /**
     * Create new transaction
     *
     * @param string $action
     * @param array $request
     * @param User|null $creator
     * @return Transaction
     */
    public function createTransaction(string $action, array $request, User $creator = null)
    {
        return Service::transaction()->create($this->transactionAccount, $action, $request, $creator);
    }

    /**
     * Get m4 api handler
     *
     * @return M4ApiInterface
     */
    public function getM4Api()
    {
        return $this->m4Api;
    }

    /**
     * Get transaction account
     *
     * @return TransactionAccountInterface
     */
    public function getTransactionAccount()
    {
        return $this->transactionAccount;
    }
}
