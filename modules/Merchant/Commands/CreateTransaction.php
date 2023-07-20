<?php

namespace Modules\Merchant\Commands;

use Illuminate\Support\Arr;
use Modules\Merchant\Models\Merchant;
use Modules\Service;
use Modules\Tenant\Models\Tenant;
use Modules\Transaction\Commands\ProcessTransaction;
use Modules\Transaction\Models\Transaction;
use Modules\User\Models\User;
use Modules\Merchant\Services\MerchantEvent;
use Throwable;

class CreateTransaction
{
    /**
     * @var array
     */
    protected $input;

    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @var User
     */
    protected $merchant;

    /**
     * CreateTransaction constructor.
     * @param Merchant $merchant
     * @param User $creator
     * @param $input
     */
    public function __construct(Merchant $merchant, $input = [], User $creator)
    {
        $this->merchant = $merchant;
        $this->creator = $creator;
        $this->input = $input;
    }


    /**
     * @return Transaction|null
     */
    public function handle()
    {
        $type = $this->input['type'];
        $purchaseUnits = [];

        $purchaseUnits[] = array_merge([
            'name' => $type,
            'customType' => $type,
        ], Arr::only($this->input, ['amount', 'description', 'memo', 'source', 'teller', 'orderId']));

        $transaction = null;
        try {
            $transaction = Service::transaction()
                ->create($this->merchant, $type, ['purchaseUnits' => $purchaseUnits], $this->creator);

            $transaction = (new ProcessTransaction($transaction))->handle();

            $this->merchant->logActivity(MerchantEvent::EDIT_WALLET, $this->creator, $purchaseUnits);

        } catch (Throwable $exception) {
            $message = $exception->getMessage();
            $message = @json_decode($message, true);
            return $message;
        }

        return $transaction;
    }
}