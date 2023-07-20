<?php

namespace Modules\Order\Commands;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Modules\Service;

/**
 * Class ListProduct
 * @package Modules\Product\Commands
 */
class MerchantListFinance
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
     * ListOrder constructor.
     * @param array $filter
     */
    public function __construct(array $filter)
    {
        $this->filter = $filter;
        $this->sort   = Arr::pull($this->filter, 'sort', 'desc');
        $this->sortBy = Arr::pull($this->filter, 'sortBy', 'id');
    }

    /**
     * @return LengthAwarePaginator|object
     */
    public function handle()
    {
        $page     = Arr::pull($this->filter, 'page', config('paginate.page'));
        $per_page = Arr::pull($this->filter, 'per_page', config('paginate.per_page'));
        $export   = Arr::pull($this->filter, 'export', false);

        $filter = $this->filter;
        $query  = Service::order()->query($filter)->getQuery();
        $query->with(['currency', 'skus', 'merchant']);

        $query = $this->setOrderBy($query);
        foreach (['warehouse_id', 'warehouse_area_id', 'payment_method'] as $key) {
            if(!empty($filter[$key])) {
                $query->groupBy('orders.id'); break;
            }
        }

        if ($export) {
            return $query;
        }

        return $query->paginate($per_page, ['orders.*'], 'page', $page);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    protected function setOrderBy($query)
    {
        $sortBy = $this->sortBy;

        $sort = $this->sort;
        $table = 'orders';
        $query->orderBy($table . '.' . $sortBy, $sort);

        return $query;
    }
}

