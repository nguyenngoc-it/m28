<?php

namespace Modules\Product\Commands;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Modules\Service;
use Modules\User\Models\User;

/**
 * Class ListProduct
 * @package Modules\Product\Commands
 */
class ListSKUs
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
        $this->filter   = $filter;
        $this->user     = $user;
    }

    /**
     * @return LengthAwarePaginator|object
     */
    public function handle()
    {
        $this->sort     = Arr::pull($this->filter, 'sort', 'desc');
        $this->sortBy   = Arr::pull($this->filter, 'sortBy', 'updated_at');
        $page           = Arr::pull($this->filter, 'page', config('paginate.page'));
        $per_page       = Arr::pull($this->filter, 'per_page', config('paginate.per_page'));
        $merchantId     = Arr::pull($this->filter, 'merchant_id');


        $query = Service::product()->skuQuery($this->filter)->getQuery();
        $query = $this->setFilterMerchantId($query, $merchantId);

        $query = $this->setOrderBy($query);
        $query = $this->withData($query);

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
        $sortBy  = $this->sortBy;

        $sort    = $this->sort;
        $table   = 'skus';
        $query->orderBy($table . '.' . $sortBy, $sort);

        return $query;
    }

    /**
     * @param Builder $query
     * @param $merchantId
     * @return mixed
     */
    protected function setFilterMerchantId($query, $merchantId = null)
    {
        if($merchantId === null) return $query;

        $query = $query
            ->join('products', 'skus.product_id', '=', 'products.id')
            ->join('product_merchants', 'products.id', '=', 'product_merchants.product_id');
        if(is_array($merchantId)) {
            $query->whereIn('product_merchants.merchant_id', $merchantId);
            $query->groupBy('skus.id');
        } else {
            $query->where('product_merchants.merchant_id', $merchantId);
        }
        return $query;
    }
}
