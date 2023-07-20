<?php

namespace Modules\User\Services;

use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Merchant\Models\Merchant;
use Modules\User\Commands\ListUser;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;

class UserService implements UserServiceInterface
{
    /**
     * Khởi tạo đối tượng query users
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter)
    {
        return (new UserQuery())->query($filter);
    }

    /**
     * Lay user he thong dai dien cho hệ thống
     *
     * @return User|null
     */
    public function getSystemUserDefault()
    {
        $userSystem = User::query()->where([
            ['tenant_id', '=', 0],
            ['username', '=', User::USERNAME_SYSTEM]
        ])->first();
        return $userSystem ? $userSystem : null;
    }

    /**
     * Lay user he thong dai dien cho hệ thống shopbase
     *
     * @return User|null
     */
    public function getUserShopBase()
    {
        $userSystem = User::query()->where([
            ['tenant_id', '=', 0],
            ['username', '=', User::USERNAME_SHOP_BASE]
        ])->first();
        return $userSystem ? $userSystem : null;
    }

    /**
     * Lay user he thong dai dien cho hệ thống fobiz
     *
     * @return User|null
     */
    public function getUserFobiz()
    {
        $userSystem = User::query()->where([
            ['tenant_id', '=', 0],
            ['username', '=', User::USERNAME_FOBIZ]
        ])->first();
        return $userSystem ? $userSystem : null;
    }


    /**
     * Lay user he thong dai dien cho hệ thống m6
     *
     * @return User|null
     */
    public function getUserM6()
    {
        $userSystem = User::query()->where([
            ['tenant_id', '=', 0],
            ['username', '=', User::USERNAME_M6]
        ])->first();
        return $userSystem ? $userSystem : null;
    }

    /**
     * @param array $filters
     * @return LengthAwarePaginator|Collection|ListUser[]|User|null
     */
    public function lists(array $filters)
    {
        return (new ListUser($filters))->handle();
    }

    /**
     * Gán toàn bộ kho quản lý cho user theo countries
     *
     * @param User $user
     * @param array $addedCountryIds
     * @return void
     */
    public function addedWarehouseByCountries(User $user, array $addedCountryIds = [])
    {
        foreach ($addedCountryIds as $addedCountryId) {
            $countryWarehouseIds = Warehouse::query()->where([
                'tenant_id' => $user->tenant_id,
                'country_id' => $addedCountryId,
                'status' => true
            ])->pluck('id')->all();
            $user->warehouses()->sync($countryWarehouseIds, false);
        }
    }

    /**
     * Gán toàn bộ Seller cho user theo countries
     *
     * @param User $user
     * @param array $addedCountryIds
     * @return void
     */
    public function addedSellerByCountries(User $user, array $addedCountryIds = [])
    {
        foreach ($addedCountryIds as $addedCountryId) {
            $countrySellerIds = Merchant::query()->where([
                'tenant_id' => $user->tenant_id,
                'location_id' => $addedCountryId
            ])->pluck('id')->all();
            $user->merchants()->sync($countrySellerIds, false);
        }
    }
}
