<?php

namespace Modules\Merchant\Services;

use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Merchant\Commands\CreateExternalMerchant;
use Modules\Merchant\Commands\CreateExternalMerchantFrom3;
use Modules\Merchant\Commands\ListMerchant;
use Modules\Merchant\Events\MerchantExternalCreate;
use Modules\Merchant\Models\Merchant;
use Modules\Merchant\Resource\DataResource;
use Modules\Product\Models\Sku;
use Modules\User\Models\User;

class MerchantService implements MerchantServiceInterface
{
    /**
     * Khởi tạo đối tượng query merchants
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter)
    {
        return (new MerchantQuery())->query($filter);
    }

    /**
     * @param array $filters
     * @return LengthAwarePaginator|Builder[]|Collection|Merchant|null
     */
    public function lists(array $filters)
    {
        return (new ListMerchant($filters))->handle();
    }

    /**
     * @param $skuCode
     * @param Merchant $merchant
     * @return Sku|null
     */
    public function getMerchantSkuByCode($skuCode, Merchant $merchant)
    {
        return $merchant->applyMerchantSkus()->where('code', $skuCode)->first();
    }

    /** mapping dữ liệu tạo seller từ vela one
     * @param array $array
     * @return mixed|void
     */
    public function createMerchant(array $inputs, User $user)
    {
        return (new CreateExternalMerchant($inputs, $user))->handle();
    }

    /** create merchant
     * @param DataResource $dataResource
     * @param User $user
     * @return CreateExternalMerchantFrom3
     */
    public function createMerchantExternal(DataResource $dataResource, User $user)
    {
        return (new CreateExternalMerchantFrom3($dataResource, $user))->handle();
    }

}
