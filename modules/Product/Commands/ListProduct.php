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
class ListProduct
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
        $this->sort     = isset($this->filter['sort']) ? $this->filter['sort'] : 'desc';
        $this->sortBy   = isset($this->filter['sortBy']) ? $this->filter['sortBy'] : 'id';
        $this->user     = $user;
    }

    /**
     * @return LengthAwarePaginator|object
     */
    public function handle()
    {
        $page       = Arr::get($this->filter, 'page', 1);
        $per_page   = Arr::get($this->filter, 'per_page', 50);
        $filter     = $this->filter;

        foreach (['sort', 'sortBy', 'user',  'page', 'per_page'] as $p) {
            if(isset($filter[$p])) {
                unset($filter[$p]);
            }
        }

        $query = Service::product()->query($filter)->getQuery();

        $query = $this->setOrderBy($query);
        $query = $this->withData($query);

        return $query->paginate($per_page, ['products.*'], 'page', $page);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    protected function withData($query)
    {
        return $query->with([
            'category',
            'unit',
            'merchant',
            'merchants',
            'supplier',
            'creator' => function ($query) {
                return $query->select(['id', 'name', 'email', 'phone', 'avatar']);
            },
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
        $table   = 'products';
        $query->orderBy($table . '.' . $sortBy, $sort);

        return $query;
    }
}
