<?php

namespace Modules\Location\Commands;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Modules\Service;
use Modules\User\Models\User;

/**
 * Class ListLocation
 * @package Modules\Location\Commands
 */
class ListLocation
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
     * ListLocation constructor.
     * @param array $filter
     */
    public function __construct(array $filter)
    {
        $this->filter   = $filter;
        $this->sort     = isset($this->filter['sort']) ? $this->filter['sort'] : 'desc';
        $this->sortBy   = isset($this->filter['sortBy']) ? $this->filter['sortBy'] : 'id';
    }

    /**
     * @return LengthAwarePaginator|object
     */
    public function handle()
    {
        $filter     = $this->filter;
        foreach (['sort', 'sortBy',  'page', 'per_page'] as $p) {
            if(isset($filter[$p])) {
                unset($filter[$p]);
            }
        }

        $query = Service::location()->query($filter)->getQuery();

        $query = $this->setOrderBy($query);

        return $query->get();
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    protected function setOrderBy($query)
    {
        $sortBy  = $this->sortBy;

        $sort    = $this->sort;
        $table   = 'locations';
        $query->orderBy($table . '.' . $sortBy, $sort);

        return $query;
    }
}
