<?php

namespace Modules\Merchant\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Illuminate\Support\Arr;
use Modules\Merchant\Models\Merchant;

class MerchantQuery extends ModelQueryFactory
{
    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new Merchant();
    }

    /**
     * @param ModelQuery $query
     * @param $code
     */
    protected function applyNameFilter(ModelQuery $query, $code)
    {
        $query->where('merchants.name', 'LIKE', '%'.trim($code).'%');
    }

    /**
     * Filter theo 	thoi gian tao
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyCreatedAtFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterTimeRange($query, 'merchants.created_at', $input);
    }
}
