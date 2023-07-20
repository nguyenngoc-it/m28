<?php

namespace Modules\Merchant\ExternalTransformers;

use App\Base\Transformer;
use Modules\Stock\Models\Stock;

class StockTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Stock $stock
     * @return mixed
     */
    public function transform($stock)
    {
        return $stock->only([
            'quantity',
            'real_quantity',
            'total_storage_fee',
            'created_at',
            'updated_at'
        ]);
    }
}
