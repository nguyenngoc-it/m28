<?php

namespace Modules\Location\Services;

use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Location\Commands\ListLocation;
use Modules\Location\Models\Location;

class LocationService implements LocationServiceInterface
{
    /**
     * Khởi tạo đối tượng query locations
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter)
    {
        return (new LocationQuery())->query($filter);
    }

    /**
     * @param array $filters
     * @return LengthAwarePaginator|Collection|ListLocation[]|Location|null
     */
    public function lists(array $filters)
    {
        return (new ListLocation($filters))->handle();
    }

    /**
     * Ds Countries đang sử dụng
     *
     * @return Collection
     */
    public function activeCountries()
    {
        return Location::query()->where('type', Location::TYPE_COUNTRY)
            ->whereNotIn('code', Location::INACTIVE_COUNTRY)->get();
    }
}
