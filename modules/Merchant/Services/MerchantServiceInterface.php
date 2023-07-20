<?php

namespace Modules\Merchant\Services;

use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Merchant\Models\Merchant;
use Modules\Merchant\Resource\DataResource;
use Modules\Product\Models\Sku;
use Modules\User\Models\User;

interface MerchantServiceInterface
{
    /**
     * Khởi tạo đối tượng query merchants
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter);

    /**
     * @param array $filters
     * @return LengthAwarePaginator|Builder[]|Collection|Merchant|null
     */
    public function lists(array $filters);

    /**
     * Lấy sku đc sử dụng bởi seller theo code,
     * ưu tiên lấy sản phẩm hệ thống
     *
     * @param $skuCode
     * @param Merchant $merchant
     * @return Sku|null
     */
    public function getMerchantSkuByCode($skuCode, Merchant $merchant);

    /** mapping dữ liệu tạo merchant từ vela one
     * @param array $array
     * @return mixed
     */
    public function createMerchant(array $inputs, User $user);

    /** create merchant
     * @param DataResource $dataResource
     * @param User $user
     * @return mixed
     */
    public function createMerchantExternal(DataResource $dataResource, User $user);
}
