<?php

namespace Modules\DeliveryNote\Commands;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Modules\Service;

/**
 * Class ListProduct
 * @package Modules\Product\Commands
 */
class ListDeliveryNote
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
        $this->sort = isset($this->filter['sort']) ? $this->filter['sort'] : 'desc';
        $this->sortBy = isset($this->filter['sortBy']) ? $this->filter['sortBy'] : 'id';
    }

    /**
     * @return LengthAwarePaginator|object
     */
    public function handle()
    {
        $page = Arr::get($this->filter, 'page', config('paginate.page'));
        $per_page = Arr::get($this->filter, 'per_page', config('paginate.per_page'));

        $filter = $this->filter;

        foreach (['sort', 'sortBy', 'page', 'per_page', 'export'] as $p) {
            if (isset($filter[$p])) {
                unset($filter[$p]);
            }
        }

        $query = Service::deliveryNote()->query($filter)->getQuery();
        $query = $this->setOrderBy($query);

        return $query->paginate($per_page, ['delivery_notes.*'], 'page', $page);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    protected function setOrderBy($query)
    {
        $sortBy = $this->sortBy;

        $sort = $this->sort;
        $table = 'delivery_notes';
        $query->orderBy($table . '.' . $sortBy, $sort);

        return $query;
    }
}

