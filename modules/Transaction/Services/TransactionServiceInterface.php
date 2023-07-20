<?php

namespace Modules\Transaction\Services;

use Modules\Transaction\Models\Transaction;
use Modules\User\Models\User;

interface TransactionServiceInterface
{
    /**
     * Create new transaction
     *
     * @param TransactionAccountInterface $account
     * @param string $action
     * @param array $request Theo format api deposit hoặc withdraw của m4
     * @param User|null $creator
     * @return Transaction
     */
    public function create(TransactionAccountInterface $account, $action, array $request, User $creator = null);

    /**
     * Perform transaction
     *
     * @param Transaction $transaction
     * @return Transaction
     */
    public function process(Transaction $transaction);

    /**
     * @param $action
     * @param $type
     * @return string
     */
    public function renderMessageType($action, $type = null);

}
