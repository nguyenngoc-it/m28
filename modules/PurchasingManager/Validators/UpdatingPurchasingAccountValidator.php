<?php

namespace Modules\PurchasingManager\Validators;

use App\Base\Validator;
use Exception;
use Gobiz\Support\Helper;
use GuzzleHttp\Exception\GuzzleException;
use Modules\PurchasingManager\Models\PurchasingAccount;

class UpdatingPurchasingAccountValidator extends Validator
{
    /** @var string */
    protected $accessToken;

    /** @var PurchasingAccount */
    protected $purchasingAccount;

    public function rules()
    {
        return [
            'id' => 'required|int',
            'password' => 'string',
            'pin_code' => 'string',
        ];
    }

    /**
     * @return PurchasingAccount
     */
    public function getPurchasingAccount(): PurchasingAccount
    {
        return $this->purchasingAccount;
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @return void
     * @throws GuzzleException
     * @throws Exception
     *
     */
    protected function customValidate()
    {
        $purchasingAccountId = $this->input('id', 0);
        $password            = $this->input('password');

        $this->purchasingAccount = PurchasingAccount::query()->where([
            'tenant_id' => $this->user->tenant_id,
            'id' => $purchasingAccountId
        ])->first();

        if (!$this->purchasingAccount) {
            $this->errors()->add('id', static::ERROR_EXISTS);
            return;
        }

        /**
         * Call M1 kiểm tra xem có kết nối tài khoản thành công hay không
         */
        try {
            $response = Helper::quickCurl($this->purchasingAccount->purchasingService->base_uri, 'oauth/token', 'post', [], [
                'username' => $this->purchasingAccount->username,
                'password' => $password,
                'grant_type' => 'password',
                'scope' => 'all',
                'client_id' => $this->purchasingAccount->purchasingService->client_id
            ]);
            if (!empty($response['access_token'])) {
                $this->accessToken = $response['access_token'];
            } else {
                $this->purchasingAccount->status = PurchasingAccount::STATUS_FAILED;
                $this->purchasingAccount->save();
                $this->errors()->add('username_password', static::ERROR_INVALID);
                return;
            }
        } catch (Exception $exception) {
            $this->purchasingAccount->status = PurchasingAccount::STATUS_FAILED;
            $this->purchasingAccount->save();
            throw new Exception($exception->getMessage());
        }
    }
}
