<?php

namespace Modules\Warehouse\Transformers;

use League\Fractal\TransformerAbstract;
use Modules\Warehouse\Models\WarehouseArea;

class WarehouseAreaTransformer extends TransformerAbstract
{
    public function transform(WarehouseArea $warehouseArea)
    {
        return [
            'id'      => (int) $warehouseArea->id,
            'address' => $warehouseArea->name,
            'name'    => $warehouseArea->name,
            'code'    => $warehouseArea->code,
        ];
    }

}
