<?php

namespace Modules\Stock\Transformers;

use App\Base\Transformer;
use Modules\Document\Models\Document;
use Modules\Product\Models\Sku;
use Modules\Stock\Models\Stock;
use Modules\Stock\Models\StockLog;
use Modules\Warehouse\Models\WarehouseArea;

class StockLogListItemTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param StockLog $stockLog
     * @return mixed
     */
    public function transform($stockLog)
    {
        $stockLogItem = $stockLog->attributesToArray();


        $action = data_get($stockLogItem, 'action');

        $payloadDocumentSkuImporting = data_get($stockLogItem, 'payload.documentSkuImporting', []);
        if ($payloadDocumentSkuImporting) {
            $documentSkuImportingId = data_get($payloadDocumentSkuImporting, 'id', 0);
            $document = Document::select('documents.*')
                                ->join('document_sku_importings', 'document_sku_importings.document_id', 'documents.id')
                                ->where('document_sku_importings.id', $documentSkuImportingId)
                                ->first();
            if ($document) {
                $payloadDocument = [
                    'id'   => $document->id,
                    'code' => $document->code,
                    'type' => $document->type,
                ];
                $stockLogItem['payload']['document'] = $payloadDocument;
            }
        }

        if ($action == Stock::ACTION_EXPORT_FOR_ORDER) {
            $payloadDocumentCode = data_get($stockLogItem, 'payload.document');
            $documentSkuImportingId = data_get($payloadDocumentSkuImporting, 'id', 0);
            $document = Document::select('documents.*')
                                ->where('documents.code', $payloadDocumentCode)
                                ->first();
            if ($document) {
                $payloadDocument = [
                    'id'   => $document->id,
                    'code' => $document->code,
                    'type' => $document->type,
                ];
                $stockLogItem['payload']['document'] = $payloadDocument;
            }
        }

        return [
            'stock_log' => $stockLogItem,
        ];
    }
}
