<?php

namespace Modules\Stock\Commands;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Service;
use Modules\Stock\Models\Stock;
use Modules\User\Models\User;

/**
 * Class ListStocks
 * @package Modules\Stock\Commands
 */
class ListStocks
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
     * @var mixed|string
     */
    protected $groupBy = 'stocks.id';

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
        $this->sort     = isset($this->filter['sort']) ? $this->filter['sort'] : 'desc';
        $this->sortBy   = isset($this->filter['sortBy']) ? $this->filter['sortBy'] : 'updated_at';
        $this->groupBy   = isset($this->filter['groupBy']) ? $this->filter['groupBy'] : 'stocks.id';
        $this->user     = $user;
    }

    /**
     * @return LengthAwarePaginator|object
     */
    public function handle()
    {
        $page = Arr::get($this->filter, 'page', config('paginate.page'));
        $per_page = Arr::get($this->filter, 'per_page', config('paginate.per_page'));
        $exportData = Arr::pull($this->filter, 'exportData', false);

        $filter = $this->filter;

        foreach (['sort', 'sortBy', 'groupBy', 'user',  'page', 'per_page', 'out_of_stock'] as $p) {
            if(isset($filter[$p])) {
                unset($filter[$p]);
            }
        }

        $select = 'MAX(stocks.id) as id,
                   stocks.sku_id,
                   MAX(stocks.warehouse_id) as warehouse_id,
                   MAX(stocks.warehouse_area_id) as warehouse_area_id,
                   MAX(stocks.quantity) as quantity,
                   MAX(stocks.real_quantity) as real_quantity,
                   MAX(stocks.total_storage_fee) as total_storage_fee,
                   MAX(stocks.real_stock) as real_stock,
                   MAX(stocks.created_at) as created_at,
                   MAX(stocks.updated_at) as updated_at
                   ';

        $query = Service::stock()->stockQuery($filter)->getQuery();
        $query->select(DB::raw($select));
        $query = $this->setFilter($query);

        $query = $this->setOrderBy($query);
        $query = $this->withData($query);
        $query->groupBy($this->groupBy);

        if($exportData) {
            return $query;
        }

        return $query->paginate($per_page, ['stocks.*'], 'page', $page);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    protected function withData($query)
    {
        return $query->with([
            'sku', 'warehouse', 'warehouseArea', 'product'
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
        $table   = 'stocks';
        $query->orderBy(DB::raw('MAX(' . $table . '.' . $sortBy . ')'), $sort);

        return $query;
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    protected function setFilter($query)
    {
        $query = $this->setFilterOutOfStock($query);

        return $query;
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    protected function setFilterOutOfStock($query)
    {
        if (isset($this->filter['out_of_stock'])) {
            if ($this->filter['out_of_stock']) {
                return $query->where('stocks.quantity', 0)
                    ->where('stocks.real_quantity', 0);
            }
            return $query
                ->where(function ($query) {
                    return $query->where('stocks.quantity', '>', 0)
                        ->orWhere('stocks.real_quantity', '>', 0);
                });
        }
        return $query;
    }
}
