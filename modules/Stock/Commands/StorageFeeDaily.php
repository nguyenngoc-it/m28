<?php

namespace Modules\Stock\Commands;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Modules\Service\Services\StorageFeeSkuStatisticQuery;

/**
 * Class ListStocks
 * @package Modules\Stock\Commands
 */
class StorageFeeDaily
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
     * ListProduct constructor.
     * @param array $filter
     */
    public function __construct(array $filter)
    {
        $this->filter = $filter;
        $this->sort   = isset($this->filter['sort']) ? $this->filter['sort'] : 'desc';
        $this->sortBy = isset($this->filter['sortBy']) ? $this->filter['sortBy'] : 'closing_time';
    }

    /**
     * @return LengthAwarePaginator
     */
    public function handle()
    {
        $page     = Arr::get($this->filter, 'page', config('paginate.page'));
        $per_page = Arr::get($this->filter, 'per_page', config('paginate.per_page'));

        foreach (['sort', 'sortBy', 'page', 'per_page'] as $p) {
            if (isset($this->filter[$p])) {
                unset($this->filter[$p]);
            }
        }

        $query = (new StorageFeeSkuStatisticQuery())->query($this->filter)->getQuery();

        return $query->paginate($per_page, ['storage_fee_sku_statistics.*'], 'page', $page);
    }
}
