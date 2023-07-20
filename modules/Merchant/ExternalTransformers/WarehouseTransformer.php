<?php

namespace Modules\Merchant\ExternalTransformers;

use App\Base\Transformer;
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
        return $warehouse->only([
            'code',
            'name',
            'description',
            'address',
            'status'
        ]);
    }
}
