<?php

namespace Modules\User\Services;

use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\User\Commands\ListUser;
use Modules\User\Models\User;

interface UserServiceInterface
{
    /**
     * Khởi tạo đối tượng query users
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter);

    /**
     * Lay user he thong dai dien cho hệ thống
     *
     * @return User|null
     */
    public function getSystemUserDefault();

    /**
     * Lay user he thong dai dien cho hệ thống shopbase
     *
     * @return User|null
     */
    public function getUserShopBase();

    /**
     * Lay user he thong dai dien cho hệ thống fobiz
     *
     * @return User|null
     */
    public function getUserFobiz();

    /**
     * Lay user he thong dai dien cho hệ thống m6
     *
     * @return User|null
     */
    public function getUserM6();

    /**
     * Lấy thông tin danh sách giao dịch viên
     *
     * @param array $filters
     * @return LengthAwarePaginator|Collection|ListUser[]|User|null
     */
    public function lists(array $filters);

    /**
     * Gán toàn bộ kho quản lý cho user theo countries
     *
     * @param User $user
     * @param array $addedCountryIds
     * @return void
     */
    public function addedWarehouseByCountries(User $user, array $addedCountryIds = []);

    /**
     * Gán toàn bộ Seller cho user theo countries
     *
     * @param User $user
     * @param array $addedCountryIds
     * @return void
     */
    public function addedSellerByCountries(User $user, array $addedCountryIds = []);
}
