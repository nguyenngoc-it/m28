<?php

namespace Modules\Warehouse\Transformers;

use App\Base\Transformer;
use Modules\Warehouse\Models\WarehouseArea;

class WarehouseAreaListItemTransformer extends Transformer
{

    /**
     * Transform the data
     *
     * @param WarehouseArea $warehouseArea
     * @return mixed
     */
    public function transform($warehouseArea)
    {
        return array_merge($warehouseArea->only([
            'warehouse'
        ]), [
            'warehouseArea' => $warehouseArea
        ]);
    }
}
