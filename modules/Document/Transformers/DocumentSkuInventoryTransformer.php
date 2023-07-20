<?php

namespace Modules\Document\Transformers;

use App\Base\Transformer;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentSkuImporting;
use Modules\Document\Models\DocumentSkuInventory;
use Modules\Document\Models\ImportingBarcode;

class DocumentSkuInventoryTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param DocumentSkuInventory $documentSkuInventory
     * @return array
     */
    public function transform($documentSkuInventory)
    {

        return array_merge($documentSkuInventory->attributesToArray(), [
            'sku' => $documentSkuInventory->sku->only('code', 'name'),
            'warehouse_area' => $documentSkuInventory->warehouseArea->only('code', 'name')
        ]);
    }
}
