<?php

namespace Modules\Currency\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\Currency\Models\Currency;

class CurrencyQuery extends ModelQueryFactory
{
    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new Currency();
    }

    /**
     * @param ModelQuery $query
     * @param $label
     */
    protected function applyLabelFilter(ModelQuery $query, $label)
    {
        $query->where('currencies.label', 'LIKE', '%'.trim($label).'%');
    }
}
