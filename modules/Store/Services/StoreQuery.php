<?php

namespace Modules\Store\Services;

use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\Store\Models\Store;

class StoreQuery extends ModelQueryFactory
{
    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new Store();
    }
}
