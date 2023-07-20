<?php

namespace Modules\Currency\Services;

use Gobiz\ModelQuery\ModelQuery;

class CurrencyService implements CurrencyServiceInterface
{
    /**
     * Khởi tạo đối tượng query currencies
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter)
    {
        return (new CurrencyQuery())->query($filter);
    }
}
