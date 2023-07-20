<?php

namespace Modules\PurchasingManager\Services;

use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Merchant\Models\Merchant;
use Modules\PurchasingManager\Models\PurchasingAccount;
use Modules\User\Models\User;

interface PurchasingManagerServiceInterface
{
    /**
     * @param array $filter
     * @return ModelQuery
     */
    public function queryPurchasingAccount(array $filter);

    /**
     * @param array $inputs
     * @param $accessToken
     * @param User $user
     * @return PurchasingAccount
     */
    public function createPurchasingAccount(array $inputs, $accessToken, User $user);

    /**
     * @param PurchasingAccount $purchasingAccount
     * @param array $inputs
     * @param $accessToken
     * @param User $user
     * @return PurchasingAccount
     */
    public function updatePurchasingAccount(PurchasingAccount $purchasingAccount, array $inputs, $accessToken, User $user);

    /**
     * @param PurchasingAccount $purchasingAccount
     * @param User $user
     * @return PurchasingAccount
     */
    public function deletePurchasingAccount(PurchasingAccount $purchasingAccount, User $user);

    /**
     * @param array $filter
     * @return LengthAwarePaginator|Builder|Builder[]|Collection
     */
    public function listPurchasingAccounts(array $filter);

    /**
     * @param Merchant $merchant
     * @param array $status
     * @return Collection
     */
    public function listPurchasingAccountsByMerchant(Merchant $merchant, array $status = []);

    /**
     * Cập nhật token cho tài khoản mua hàng
     *
     * @param PurchasingAccount $purchasingAccount
     * @return void
     */
    public function updatePurchasingAccountToken(PurchasingAccount $purchasingAccount);
}
