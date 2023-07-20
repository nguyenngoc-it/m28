<?php

namespace Modules\InvalidOrder\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\InvalidOrder\Models\InvalidOrder;

class InvalidOrderQuery extends ModelQueryFactory
{
    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new InvalidOrder();
    }

}
