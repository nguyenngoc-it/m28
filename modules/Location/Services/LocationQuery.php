<?php

namespace Modules\Location\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\Location\Models\Location;

class LocationQuery extends ModelQueryFactory
{
    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new Location();
    }

    /**
     * @param ModelQuery $query
     * @param $label
     */
    protected function applyLabelFilter(ModelQuery $query, $label)
    {
        $query->where('locations.label', 'LIKE', '%'.trim($label).'%');
    }

    /**
     * Filter theo 	thoi gian tao
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyCreatedAtFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterTimeRange($query, 'locations.created_at', $input);
    }

}
