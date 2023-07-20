<?php

namespace Modules\Document\Transformers;

use League\Fractal\TransformerAbstract;
use Modules\Document\Models\ImportingBarcode;

class ImportingBarcodeTransformerNew extends TransformerAbstract
{
    public function transform(ImportingBarcode $importingBarcode)
    {
        return [
            'id' => $importingBarcode->id,
            'type' => $importingBarcode->type,
            'barcode' => $importingBarcode->barcode,
            'imported_type' => $importingBarcode->imported_type
        ];
    }

}
