<?php

namespace Modules\PurchasingManager\Services;

use Carbon\Carbon;
use Exception;
use Gobiz\ModelQuery\ModelQuery;
use Gobiz\Support\Helper;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Modules\Merchant\Models\Merchant;
use Modules\PurchasingManager\Models\PurchasingAccount;
use Modules\Service;
use Modules\User\Models\User;

class PurchasingManagerService implements PurchasingManagerServiceInterface
{
    /**
     * @param array $filter
     * @return ModelQuery
     */
    public function queryPurchasingAccount(array $filter)
    {
        return (new PurchasingAccountQuery())->query($filter);
    }

    /**
     * @param array $inputs
     * @param $accessToken
     * @param User $user
     * @return PurchasingAccount
     */
    public function createPurchasingAccount(array $inputs, $accessToken, User $user)
    {
        $encryptPassword   = openssl_encrypt(Arr::get($inputs, 'password'), 'AES-128-ECB', env('purchase.secret_password'));
        $encryptPinCode    = openssl_encrypt(Arr::get($inputs, 'pin_code'), 'AES-128-ECB', env('purchase.secret_password'));
        $purchasingAccount = PurchasingAccount::withTrashed()->updateOrCreate(
            [
                'tenant_id' => $user->tenant_id,
                'purchasing_service_id' => Arr::get($inputs, 'purchasing_service_id'),
                'username' => Arr::get($inputs, 'username'),
            ],
            [
                'merchant_id' => Arr::get($inputs, 'merchant_id', 0),
                'password' => $encryptPassword,
                'pin_code' => $encryptPinCode,
                'token' => $accessToken,
                'status' => PurchasingAccount::STATUS_ACTIVE,
                'creator_id' => $user->id,
                'deleted_at' => null,
                'refresh_token_at' => Carbon::now(),
            ]
        );
        $purchasingAccount->logActivity(PurchasingAccountEvent::CREATE, $user);

        return $purchasingAccount;
    }

    /**
     * @param PurchasingAccount $purchasingAccount
     * @param array $inputs
     * @param $accessToken
     * @param User $user
     * @return PurchasingAccount
     */
    public function updatePurchasingAccount(PurchasingAccount $purchasingAccount, array $inputs, $accessToken, User $user)
    {
        $encryptPassword                     = openssl_encrypt(Arr::get($inputs, 'password'), 'AES-128-ECB', env('purchase.secret_password'));
        $encryptPinCode                      = openssl_encrypt(Arr::get($inputs, 'pin_code'), 'AES-128-ECB', env('purchase.secret_password'));
        $payloadLog                          = [];
        $purchasingAccount->token            = $accessToken;
        $purchasingAccount->refresh_token_at = Carbon::now();
        if ($purchasingAccount->password != $encryptPassword) {
            $purchasingAccount->password = $encryptPassword;
            $payloadLog['fields'][]      = 'password';
        }
        if ($purchasingAccount->pin_code != $encryptPinCode) {
            $purchasingAccount->pin_code = $encryptPinCode;
            $payloadLog['fields'][]      = 'pin_code';
        }
        $purchasingAccount->status = PurchasingAccount::STATUS_ACTIVE;
        $purchasingAccount->save();
        $purchasingAccount->logActivity(PurchasingAccountEvent::UPDATE, $user, $payloadLog);

        return $purchasingAccount;
    }

    /**
     * @param PurchasingAccount $purchasingAccount
     * @param User $user
     * @return PurchasingAccount
     * @throws Exception
     */
    public function deletePurchasingAccount(PurchasingAccount $purchasingAccount, User $user)
    {
        $purchasingAccount->delete();
        $purchasingAccount->logActivity(PurchasingAccountEvent::DELETE, $user);
        return $purchasingAccount;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator|Builder|Builder[]|Collection
     */
    public function listPurchasingAccounts(array $filter)
    {
        $sortBy    = Arr::get($filter, 'sort_by', 'id');
        $sortByIds = Arr::get($filter, 'sort_by_ids', false);
        $sort      = Arr::get($filter, 'sort', 'desc');
        $page      = Arr::get($filter, 'page', config('paginate.page'));
        $perPage   = Arr::get($filter, 'per_page', config('paginate.per_page'));
        $paginate  = Arr::get($filter, 'paginate', true);
        $ids       = Arr::get($filter, 'ids', []);

        foreach (['sort', 'sort_by', 'page', 'per_page', 'sort_by_ids', 'paginate'] as $p) {
            if (isset($filter[$p])) {
                unset($filter[$p]);
            }
        }

        $query = Service::purchasingManager()->queryPurchasingAccount($filter)->getQuery();
        $query->with(['purchasingService', 'merchant', 'creator']);
        if ($sortByIds) {
            $query->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')');
        } else {
            $query->orderBy('purchasing_accounts' . '.' . $sortBy, $sort);
        }

        if (!$paginate) {
            return $query->get();
        }

        return $query->paginate($perPage, ['purchasing_acounts.*'], 'page', $page);
    }

    /**
     * @param Merchant $merchant
     * @param array $status
     * @return Collection
     */
    public function listPurchasingAccountsByMerchant(Merchant $merchant, array $status = [])
    {
        $query = PurchasingAccount::query()->where('merchant_id', $merchant->id);
        if ($status) {
            $query->whereIn('status', $status);
        }
        return $query->get();
    }

    /**
     * Cập nhật token cho tài khoản mua hàng
     *
     * @param PurchasingAccount $purchasingAccount
     * @return void
     * @throws GuzzleException
     */
    public function updatePurchasingAccountToken(PurchasingAccount $purchasingAccount)
    {
        if ((time() - $purchasingAccount->refresh_token_at->timestamp) > PurchasingAccount::REFRESH_TOKEN_TIME) {
            $response = Helper::quickCurl($purchasingAccount->purchasingService->base_uri, 'oauth/token', 'post', [], [
                'username' => $purchasingAccount->username,
                'password' => openssl_decrypt($purchasingAccount->password, 'AES-128-ECB', env('purchase.secret_password')),
                'grant_type' => 'password',
                'scope' => 'all',
                'client_id' => $purchasingAccount->purchasingService->client_id
            ]);
            if (!empty($response['access_token'])) {
                $purchasingAccount->token            = $response['access_token'];
                $purchasingAccount->refresh_token_at = Carbon::now();
                $purchasingAccount->save();
            }
        }
    }
}
