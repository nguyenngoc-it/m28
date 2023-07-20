<?php

namespace Modules\ImportHistory\Commands;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Modules\Service;
use Modules\ImportHistory\Models\ImportHistory;
use Modules\User\Models\User;

/**
 * Class ListImportHistories
 * @package Modules\ImportHistory\Commands
 */
class ListImportHistories
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
        $this->sortBy   = isset($this->filter['sortBy']) ? $this->filter['sortBy'] : 'updated_at';
        $this->user     = $user;
    }

    /**
     * @return LengthAwarePaginator|object
     */
    public function handle()
    {
        $page = Arr::get($this->filter, 'page', config('paginate.page'));
        $per_page = Arr::get($this->filter, 'per_page', config('paginate.per_page'));

        $filter = $this->filter;

        foreach (['sort', 'sortBy', 'user',  'page', 'per_page'] as $p) {
            if(isset($filter[$p])) {
                unset($filter[$p]);
            }
        }

        $query = Service::importHistory()->importHistoryQuery($filter)->getQuery();
        $query = $this->setOrderBy($query);
        $query = $this->withData($query);
        foreach (['sku_id', 'warehouse_area_id', 'warehouse_id'] as $key) {
            if(!empty($filter[$key])) {
                $query->groupBy('import_histories.id'); break;
            }
        }

        return $query->paginate($per_page, ['import_histories.*'], 'page', $page);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    protected function withData($query)
    {
        return $query->with([
            'creator'
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
        $table   = 'import_histories';
        $query->orderBy($table . '.' . $sortBy, $sort);

        return $query;
    }
}
