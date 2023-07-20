<?php

namespace Modules\Service\Commands;

use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\Service\Models\ServicePack;

class ServicePackQuery extends ModelQueryFactory
{
    protected $joins = [];

    /**
     * Khởi tạo model
     */
    protected function newModel(): ServicePack
    {
        return new ServicePack();
    }
}
