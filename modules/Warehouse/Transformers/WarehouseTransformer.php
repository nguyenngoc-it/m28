<?php

namespace Modules\Warehouse\Transformers;

use App\Base\Transformer;
use Modules\Service;
use Modules\Warehouse\Models\Warehouse;

class WarehouseTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Warehouse $warehouse
     * @return mixed
     */
    public function transform($warehouse)
    {
        return array_merge($warehouse->attributesToArray());
    }
}
