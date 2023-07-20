<?php

namespace Modules\Supplier\Transformers;

use App\Base\Transformer;
use Modules\Supplier\Models\Supplier;

class SupplierListItemTransformer extends Transformer
{

    /**
     * Transform the data
     *
     * @param Supplier $supplier
     * @return mixed
     */
    public function transform($supplier)
    {
        return compact('supplier');
    }
}
