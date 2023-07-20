<?php

namespace Modules\Product\Commands;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Modules\Product\Models\ProductMerchant;
use Modules\Service;
use Modules\User\Models\User;

/**
 * Class ListProduct
 * @package Modules\Product\Commands
 */
class ListSellerSKUs
{
    /**
     * @var array
     */
    protected $filter = [];

    /**
     * @var mixed|string
     */
    protected $sort = 'desc';

    /**
     * @var mixed|string
     */
    protected $sortBy = 'id';

    /**
     * @var User
     */
    protected $user;

    /**
     * ListProduct constructor.
     * @param array $filter
     * @param User $user
     */
    public function __construct(array $filter, User $user)
    {
        $this->filter = $filter;
        $this->sort   = Arr::pull($this->filter, 'sort', 'desc');
        $this->sortBy = Arr::pull($this->filter, 'sortBy', 'updated_at');
        $this->user   = $user;
    }

    /**
     * @return LengthAwarePaginator|Builder
     */
    public function handle()
    {
        $page       = Arr::pull($this->filter, 'page', config('paginate.page'));
        $per_page   = Arr::pull($this->filter, 'per_page', config('paginate.per_page'));
        $exportData = Arr::pull($this->filter, 'export_data', false);
        $filter     = $this->filter;

        $productIds = ProductMerchant::query()->where('merchant_id', $this->user->merchant->id)
            ->pluck('product_id')->toArray();

        $filter['product_id'] = $productIds;
        $query                = Service::product()->skuQuery($filter)->getQuery();
        $query                = $this->setOrderBy($query);
        $query                = $this->withData($query);

        if ($exportData) {
            return $query;
        }

        return $query->paginate($per_page, ['skus.*'], 'page', $page);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    protected function withData($query)
    {
        return $query->with([
            'unit',
            'category',
            'warehouseStocks'
        ]);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    protected function setOrderBy($query)
    {
        $sortBy = $this->sortBy;
        $sort   = $this->sort;
        $table  = 'skus';
        $query->orderBy($table . '.' . $sortBy, $sort);

        return $query;
    }
}
