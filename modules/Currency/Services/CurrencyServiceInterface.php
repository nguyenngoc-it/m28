<?php

namespace Modules\Currency\Services;

use Gobiz\ModelQuery\ModelQuery;

interface CurrencyServiceInterface
{
    /**
     * Khởi tạo đối tượng query currencies
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter);
}
