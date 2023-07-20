<?php

namespace Modules\Document\Transformers;

use App\Base\Transformer;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentFreightBillInventory;
use Modules\Document\Models\DocumentSkuImporting;
use Modules\Document\Models\DocumentSkuInventory;
use Modules\Document\Models\ImportingBarcode;

class DocumentFreightBillInventoryTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param DocumentFreightBillInventory $documentFreightBillInventory
     * @return array
     */
    public function transform($documentFreightBillInventory)
    {
        return array_merge($documentFreightBillInventory->attributesToArray(), [
                'currency' => $documentFreightBillInventory->order->currency,
                'total_amount' => $documentFreightBillInventory->getTotalAmount()
            ]
        );
    }
}
