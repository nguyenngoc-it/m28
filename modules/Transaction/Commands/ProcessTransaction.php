<?php

namespace Modules\Transaction\Commands;

use App\Base\CommandBus;
use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Modules\Gobiz\Services\M4ApiInterface;
use Modules\Merchant\Models\Merchant;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Supplier\Models\Supplier;
use Modules\Transaction\Models\Transaction;
use Throwable;

class ProcessTransaction extends CommandBus
{
    /**
     * @var Transaction
     */
    protected $transaction;

    /**
     * ProcessTransaction constructor
     *
     * @param Transaction $transaction
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * @return Transaction
     * @throws Throwable
     */
    public function handle()
    {
        $transaction = $this->transaction;

        if ($transaction->status === Transaction::STATUS_SUCCESS) {
            return $transaction;
        }

        $transaction->update(['status' => Transaction::STATUS_PROCESSING]);
        $transaction->log('Start process');

        try {
            $res = $this->request();
        } catch (Throwable $exception) {
            $transaction->update(['status' => Transaction::STATUS_FAILED]);
            $transaction->log("Process failed: {$exception->getMessage()}");

            throw $exception;
        }

        $transaction->update([
            'status' => Transaction::STATUS_SUCCESS,
            'response' => $res,
        ]);

        $transaction->log('Process success');

        return $transaction;
    }

    /**
     * @return array
     * @throws RestApiException
     */
    protected function request()
    {
        /**
         * @var M4ApiInterface $m4Api
         */
        $transaction = $this->transaction;

        list($m4Api, $account) = $this->resolveM4Account();

        $payload = $this->makePayload();

        switch ($transaction->action) {
            case Transaction::ACTION_REFUND:
            {
                return $m4Api->refund($account, $payload, $this->transaction->_id)->getData();
            }
            case Transaction::ACTION_COLLECT:
            {
                return $m4Api->collect($account, $payload, $this->transaction->_id)->getData();
            }
            case Transaction::ACTION_DEPOSIT:
            {
                return $m4Api->deposit($account, $payload, $this->transaction->_id)->getData();
            }
            case Transaction::ACTION_WITHDRAW:
            {
                return $m4Api->withdraw($account, $payload, $this->transaction->_id)->getData();
            }
            default:
            {
                throw new InvalidArgumentException("The transaction action $transaction->action invalid");
            }
        }
    }

    /**
     * @return array
     */
    protected function resolveM4Account()
    {
        $transaction = $this->transaction;

        switch ($transaction->account_type) {
            case Transaction::ACCOUNT_TYPE_MERCHANT:
            {
                return [
                    $transaction->tenant->m4Merchant(),
                    Merchant::find($transaction->account_id)->code,
                ];
            }

            case Transaction::ACCOUNT_TYPE_SHIPPING_PARTNER:
            {
                return [
                    $transaction->tenant->m4ShippingPartner(),
                    ShippingPartner::find($transaction->account_id)->code,
                ];
            }

            case Transaction::ACCOUNT_TYPE_SUPPLIER_INVENTORY:
            case Transaction::ACCOUNT_TYPE_SUPPLIER_SOLD:
            {
                $supplier = Supplier::find($transaction->account_id);

                $wallet = $transaction->account_type === Transaction::ACCOUNT_TYPE_SUPPLIER_INVENTORY
                    ? $supplier->inventoryWallet()
                    : $supplier->soldWallet();

                return [
                    $wallet->getM4Api(),
                    $wallet->account(),
                ];
            }

            default:
            {
                throw new InvalidArgumentException("Cant resolve M4 account for transaction {$transaction->_id}");
            }
        }
    }

    /**
     * @return array
     */
    protected function makePayload()
    {
        $payload = array_merge([
            'teller' => $this->transaction->creator->username,
            'source' => config('app.name'),
        ], $this->transaction->request);

        $payload['purchaseUnits'] = array_map(function (array $unit) {
            return array_merge($unit, [
                'referenceId' => trim($this->transaction->_id . '-' . Arr::get($unit, 'referenceId'), '-'),
            ]);
        }, $payload['purchaseUnits']);

        return $payload;
    }
}
