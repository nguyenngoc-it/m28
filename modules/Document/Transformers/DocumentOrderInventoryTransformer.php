<?php

namespace Modules\Document\Transformers;

use App\Base\Transformer;
use Modules\Document\Models\DocumentOrderInventory;

class DocumentOrderInventoryTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param DocumentOrderInventory $documentOrderInventory
     * @return mixed
     */
    public function transform($documentOrderInventory)
    {
        return array_merge($documentOrderInventory->attributesToArray(), [
            'order_exporting' => $documentOrderInventory->orderExporting ? $documentOrderInventory->orderExporting : null,
        ]);
    }
}
