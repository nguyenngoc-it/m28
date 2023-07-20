<?php

namespace Modules\PurchasingManager\Validators;

use App\Base\Validator;
use Exception;
use Gobiz\Support\Helper;
use GuzzleHttp\Exception\GuzzleException;
use Modules\Merchant\Models\Merchant;
use Modules\PurchasingManager\Models\PurchasingAccount;
use Modules\PurchasingManager\Models\PurchasingService;

class CreatingPurchasingAccountValidator extends Validator
{
    /** @var string|null */
    protected $accessToken;
    /** @var PurchasingAccount|null */
    protected $deletePurchasingAccount;

    public function rules()
    {
        return [
            'purchasing_service_id' => 'required|int',
            'merchant_id' => 'int',
            'username' => 'required|string',
            'password' => 'required|string',
            'pin_code' => 'string',
        ];
    }

    /**
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    /**
     * @return PurchasingAccount|null
     */
    public function getDeletePurchasingAccount(): ?PurchasingAccount
    {
        return $this->deletePurchasingAccount;
    }

    /**
     * @return void
     * @throws GuzzleException
     * @throws Exception
     *
     */
    protected function customValidate()
    {
        $purchasingServiceId = $this->input('purchasing_service_id', 0);
        $merchantId          = $this->input('merchant_id', 0);
        $username            = $this->input('username');
        $password            = $this->input('password');

        /** @var PurchasingService $purchasingService */
        $purchasingService = PurchasingService::query()->where('id', $purchasingServiceId)->first();
        if (!$purchasingService) {
            $this->errors()->add('purchasing_service', static::ERROR_EXISTS);
            return;
        }
        if ($merchantId && !$merchant = Merchant::query()->where(['tenant_id' => $this->user->tenant_id, 'id' => $merchantId])->first()) {
            $this->errors()->add('merchant', static::ERROR_EXISTS);
            return;
        }

        /**
         * Tài khoản đã tồn tại
         */
        /** @var PurchasingAccount|null $existPurchasingAccount */
        $existPurchasingAccount = PurchasingAccount::withTrashed()->where([
            'tenant_id' => $this->user->tenant_id,
            'purchasing_service_id' => $purchasingServiceId,
            'username' => $username
        ])->first();
        if ($existPurchasingAccount) {
            /**
             * Khôi phục tài khoản
             */
            if ($existPurchasingAccount->deleted_at && $existPurchasingAccount->merchant_id == $merchantId) {
                $this->deletePurchasingAccount = $existPurchasingAccount;
            }
            /**
             * Tài khoản đã tồn tại
             */
            if (!$existPurchasingAccount->deleted_at) {
                $this->errors()->add('username', static::ERROR_ALREADY_EXIST);
                return;
            }
        }

        /**
         * Call M1 kiểm tra xem có kết nối tài khoản thành công hay không
         */
        try {
            $response = Helper::quickCurl($purchasingService->base_uri, 'oauth/token', 'post', [], [
                'username' => $username,
                'password' => $password,
                'grant_type' => 'password',
                'scope' => 'all',
                'client_id' => $purchasingService->client_id
            ]);
            if (!empty($response['access_token'])) {
                $this->accessToken = $response['access_token'];
            } else {
                $this->errors()->add('username_password', static::ERROR_INVALID);
                return;
            }
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
}
