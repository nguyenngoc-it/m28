<?php

namespace Modules\PurchasingManager\Validators;

use App\Base\Validator;
use Exception;
use Gobiz\Support\Helper;
use GuzzleHttp\Exception\GuzzleException;
use Modules\Merchant\Models\Merchant;
use Modules\PurchasingManager\Models\PurchasingAccount;
use Modules\PurchasingManager\Models\PurchasingService;

class CreatingMerchantPurchasingAccountApiValidator extends Validator
{
    /** @var string|null */
    protected $accessToken;
    /** @var PurchasingAccount|null */
    protected $deletePurchasingAccount;

    public function rules()
    {
        return [
            'purchasing_service_id' => 'required|int',
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
        $purchasingServiceId = data_get($this->input, 'purchasing_service_id', 0);
        $username            = data_get($this->input, 'username');
        $password            = data_get($this->input, 'password');
        $merchantId          = data_get($this->input, 'merchant_id', 0);

        $merchant = Merchant::find($merchantId);
        if ($merchant) {
            $tenantId = $merchant->tenant_id;
        } else {
            $tenantId = 0;
        }

        /** @var PurchasingService $purchasingService */
        $purchasingService = PurchasingService::query()->where('id', $purchasingServiceId)->first();
        if (!$purchasingService) {
            $this->errors()->add('purchasing_service', static::ERROR_EXISTS);
            return;
        }

        /**
         * Tài khoản đã tồn tại
         */
        /** @var PurchasingAccount|null $existPurchasingAccount */
        $existPurchasingAccount = PurchasingAccount::withTrashed()->where([
            'tenant_id' => $tenantId,
            'purchasing_service_id' => $purchasingServiceId,
            'username' => $username
        ])->first();
        if ($existPurchasingAccount) {
            /**
             * Khôi phục tài khoản
             */
            if ($existPurchasingAccount->deleted_at && $existPurchasingAccount->merchant_id == $merchant->id) {
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
            $this->errors()->add('error_connect', $exception->getMessage());
            return;
            // throw new Exception($exception->getMessage());
        }
    }
}
