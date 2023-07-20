<?php

namespace Modules\Merchant\ExternalTransformers;

use App\Base\Transformer;
use Modules\Warehouse\Models\WarehouseArea;

class WarehouseAreaTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param WarehouseArea $warehouseArea
     * @return mixed
     */
    public function transform($warehouseArea)
    {
        return $warehouseArea->only([
            'code',
            'name',
            'description',
            'movable',
            'status'
        ]);
    }
}
