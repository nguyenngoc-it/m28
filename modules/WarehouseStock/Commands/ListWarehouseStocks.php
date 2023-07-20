<?php

namespace Modules\WarehouseStock\Commands;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Modules\Service;
use Modules\WarehouseStock\Models\WarehouseStock;
use Modules\User\Models\User;

/**
 * Class ListWarehouseStocks
 * @package Modules\WarehouseStock\Commands
 */
class ListWarehouseStocks
{
    /**
     * @var array
     */
    protected $filter = [];

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
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function handle()
    {
        $filter     = $this->filter;
        $sortBy     = Arr::pull($filter, 'sort_by', 'id');
        $sort       = Arr::pull($filter, 'sort', 'desc');
        $page       = Arr::pull($filter, 'page', config('paginate.page'));
        $perPage    = Arr::pull($filter, 'per_page', config('paginate.per_page'));

        $query = Service::warehouseStock()->warehouseStockQuery($filter)->getQuery();
        $query->orderBy('warehouse_stocks' . '.' . $sortBy, $sort);
        $query->with([
            'sku', 'warehouse', 'product',
        ]);

        return $query->paginate($perPage, ['warehouse_stocks.*'], 'page', $page);
    }
}
