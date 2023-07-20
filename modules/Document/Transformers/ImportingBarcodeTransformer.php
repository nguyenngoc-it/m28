<?php

namespace Modules\Document\Transformers;

use App\Base\Transformer;
use Modules\Document\Models\Document;
use Modules\Document\Models\ImportingBarcode;

class ImportingBarcodeTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param ImportingBarcode $importingBarcode
     * @return array
     */
    public function transform($importingBarcode)
    {
        $order  = $importingBarcode->freightBill ? $importingBarcode->freightBill->order : null;
        $result = [
            'importing_barcode' => $importingBarcode->attributesToArray(),
            'freight_bill' => $importingBarcode->freightBill ? $importingBarcode->freightBill->only(['id', 'freight_bill_code', 'status']) : null,
            'order' => $order ? $order->only(['id', 'code', 'status']) : null,
        ];
        if ($importingBarcode->document && $importingBarcode->document->type == Document::TYPE_IMPORTING_RETURN_GOODS && $order) {
            $result['order_skus'] = $order->orderSkus;
        }
        return $result;
    }
}
