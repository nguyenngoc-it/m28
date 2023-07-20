<?php

namespace Modules\Stock\Commands;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Modules\Service;
use Modules\User\Models\User;

/**
 * Class ListStockLogs
 * @package Modules\Stock\Commands
 */
class ListStockLogs
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
        $page         = Arr::pull($this->filter, 'page', config('paginate.page'));
        $per_page     = Arr::pull($this->filter, 'per_page', config('paginate.per_page'));
        $realQuantity = Arr::pull($this->filter, 'real_quantity');
        $exportData   = Arr::pull($this->filter, 'export_data');

        $filter = $this->filter;
        $query  = Service::stock()->stockLogQuery($filter)->getQuery();

        if ($realQuantity === null) {
            $query->whereNotNull('real_quantity');
        }

        $query = $this->setOrderBy($query);
        if ($exportData) {
            return $query;
        }

        return $query->paginate($per_page, ['stock_logs.*'], 'page', $page);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    protected function setOrderBy($query)
    {
        $sortBy = $this->sortBy;

        $sort  = $this->sort;
        $table = 'stock_logs';
        $query->orderBy($table . '.' . $sortBy, $sort);

        return $query;
    }
}
