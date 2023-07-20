<?php

namespace Modules\User\Commands;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Modules\Service;
use Modules\User\Models\User;

/**
 * Class ListUser
 * @package Modules\User\Commands
 */
class ListUser
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
     */
    public function __construct(array $filter)
    {
        $this->filter   = $filter;
        $this->sort     = isset($this->filter['sort']) ? $this->filter['sort'] : 'desc';
        $this->sortBy   = isset($this->filter['sortBy']) ? $this->filter['sortBy'] : 'updated_at';
    }

    /**
     * @return LengthAwarePaginator|object
     */
    public function handle()
    {
        $page = Arr::get($this->filter, 'page', config('paginate.page'));
        $per_page = Arr::get($this->filter, 'per_page', config('paginate.per_page'));
        $filter = $this->filter;
        foreach (['sort', 'sortBy', 'user',  'page', 'per_page', 'paginate'] as $p) {
            if(isset($filter[$p])) {
                unset($filter[$p]);
            }
        }

        $paginate = Arr::get($this->filter, 'paginate', true);
        if(!$paginate) {
            return Service::user()->query($filter)->getQuery()->get();
        }

        $query = Service::user()->query($filter)->getQuery();
        $query = $this->setOrderBy($query);
        $query = $this->withData($query);

        if(!empty($filter['location_id'])) {
            $query->groupBy('users.id');
        }

        return $query->paginate($per_page, ['users.*'], 'page', $page);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    protected function withData($query)
    {
        return $query->with([
            'merchants',
            'warehouses',
            'locations',
            'suppliers'
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
        $table   = 'users';
        $query->orderBy($table . '.' . $sortBy, $sort);

        return $query;
    }
}
