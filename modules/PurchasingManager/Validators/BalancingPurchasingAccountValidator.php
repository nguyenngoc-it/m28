<?php

namespace Modules\PurchasingManager\Validators;

use App\Base\Validator;
use Exception;
use Gobiz\Support\Helper;
use GuzzleHttp\Exception\GuzzleException;
use Modules\PurchasingManager\Models\PurchasingAccount;
use Modules\Service;

class BalancingPurchasingAccountValidator extends Validator
{
    /** @var float */
    protected $balance;

    public function rules()
    {
        return [
            'id' => 'required|int'
        ];
    }

    /**
     * @return float
     */
    public function getBalance(): float
    {
        return $this->balance;
    }

    /**
     * @return void
     * @throws GuzzleException
     *
     */
    protected function customValidate()
    {
        $purchasingAccountId = $this->input('id', 0);

        /** @var PurchasingAccount $purchasingAccount */
        $purchasingAccount = PurchasingAccount::query()->where([
            'tenant_id' => $this->user->tenant_id,
            'id' => $purchasingAccountId
        ])->first();

        if (!$purchasingAccount) {
            $this->errors()->add('id', static::ERROR_EXISTS);
            return;
        }

        /**
         * Kiểm tra số dư tk
         */
        try {
            Service::purchasingManager()->updatePurchasingAccountToken($purchasingAccount);
            $response = Helper::quickCurl($purchasingAccount->purchasingService->base_uri, 'api/customer/profile/balance', 'get', [
                'Authorization' => 'Bearer ' . $purchasingAccount->token,
                'Content-Type' => 'application/json'
            ]);
            if (!empty($response['balance'])) {
                $this->balance = $response['balance'];
            } else {
                $purchasingAccount->status = PurchasingAccount::STATUS_FAILED;
                $purchasingAccount->save();
                $this->errors()->add('balance', static::ERROR_NOT_FOUND);
                return;
            }
        } catch (Exception $exception) {
            $purchasingAccount->status = PurchasingAccount::STATUS_FAILED;
            $purchasingAccount->save();
            $this->errors()->add('balance', $exception->getMessage());
            return;
        }
    }
}
