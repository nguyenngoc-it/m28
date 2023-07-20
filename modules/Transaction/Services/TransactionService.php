<?php

namespace Modules\Transaction\Services;

use Modules\Service;
use Modules\Transaction\Commands\ProcessTransaction;
use Modules\Transaction\Models\Transaction;
use Modules\User\Models\User;

class TransactionService implements TransactionServiceInterface
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
    public function create(TransactionAccountInterface $account, $action, array $request, User $creator = null)
    {
        $creator = $creator ?: Service::user()->getSystemUserDefault();

        return Transaction::create([
            'tenant_id' => $account->getTenantId(),
            'account_type' => $account->getAccountType(),
            'account_id' => $account->getAccountId(),
            'creator_id' => $creator->id,
            'action' => $action,
            'request' => $request,
            'status' => Transaction::STATUS_PENDING,
        ]);
    }

    /**
     * Perform transaction
     *
     * @param Transaction $transaction
     * @return Transaction
     */
    public function process(Transaction $transaction)
    {
        return (new ProcessTransaction($transaction))->dispatch();
    }

    /**
     * @param $action
     * @param $type
     * @return string
     */
    public function renderMessageType($action, $type = null)
    {
        switch ($type) {
            case Transaction::TYPE_IMPORT_SERVICE:
            case Transaction::TYPE_EXPORT_SERVICE:
            case Transaction::TYPE_SHIPPING:
            case Transaction::TYPE_EXTENT:
            case Transaction::TYPE_STORAGE_FEE:
            case Transaction::TYPE_IMPORT_RETURN_GOODS_SERVICE:
            case Transaction::TYPE_COST_OF_GOODS:
                if ($action == Transaction::ACTION_COLLECT) {
                    return strtolower($action . '_' . $type);
                }
                break;
            case Transaction::TYPE_COD:
            case Transaction::TYPE_DEPOSIT:
                if ($action == Transaction::ACTION_DEPOSIT) {
                    return strtolower($action . '_' . $type);
                }
                break;
            case Transaction::TYPE_WITHDRAW:
                if ($action == Transaction::ACTION_WITHDRAW) {
                    return strtolower($action . '_' . $type);
                }
                break;
            default:
                return strtolower($action);

        }
        return strtolower($action);
    }
}
