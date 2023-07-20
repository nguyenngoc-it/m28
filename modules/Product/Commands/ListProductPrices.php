<?php

namespace Modules\Product\Commands;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Modules\Service;
use Modules\User\Models\User;

/**
 * Class ListProductPrices
 * @package Modules\Product\Commands
 */
class ListProductPrices
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
        $this->sort   = Arr::pull($this->filter, 'sort', 'desc');
        $this->sortBy = Arr::pull($this->filter, 'sortBy', 'id');
        $page         = Arr::pull($this->filter, 'page', config('paginate.page'));
        $per_page     = Arr::pull($this->filter, 'per_page', config('paginate.per_page'));

        $filter = $this->filter;
        $query = Service::product()->productPriceQuery($filter)->getQuery();

        $query = $this->setOrderBy($query);
        $query = $this->withData($query);

        return $query->paginate($per_page, ['product_prices.*'], 'page', $page);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    protected function withData($query)
    {
        return $query->with([
            'product',
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
        $table   = 'product_prices';
        $query->orderBy($table . '.' . $sortBy, $sort);

        return $query;
    }

}
