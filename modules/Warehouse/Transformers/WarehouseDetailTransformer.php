<?php

namespace Modules\Warehouse\Transformers;

use App\Base\Transformer;
use Modules\Warehouse\Models\Warehouse;
use Modules\Service;

class WarehouseDetailTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Warehouse $warehouse
     * @return mixed
     */
    public function transform($warehouse)
    {
        return array_merge($warehouse->only([
            'tenant', 'areas',
            'country', 'province', 'district', 'ward'
        ]), [
            'warehouse' => $warehouse
        ]);
    }
}
