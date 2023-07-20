<?php

namespace Modules\SupplierTransaction\Transformers;

use App\Base\Transformer;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentOrder;
use Modules\Document\Models\DocumentSupplierTransaction;
use Modules\Order\Models\Order;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\SupplierTransaction\Models\SupplierTransaction;

class SupplierTransactionTransformer extends Transformer
{
    /**
     * @param SupplierTransaction $supplierTransaction
     * @return array
     */
    public function transform($supplierTransaction)
    {
        $reference = null;
        $note      = null;
        switch ($supplierTransaction->object_type) {
            case SupplierTransaction::OBJECT_PURCHASING_PACKAGE:
                $purchasingPackage = $supplierTransaction->purchasingPackage;
                $reference         = $purchasingPackage->code;
                $note              = $purchasingPackage->note;
                break;
            case SupplierTransaction::OBJECT_ORDER:
                $order = $supplierTransaction->order;
                if ($supplierTransaction->type == SupplierTransaction::TYPE_IMPORT_BY_RETURN) {
                    /** @var Document $document */
                    $document  = $order->documents()->where('type', Document::TYPE_IMPORTING_RETURN_GOODS)
                        ->where('status', Document::STATUS_COMPLETED)->first();
                    $reference = $document->code;
                    $note      = $document->note;
                } else {
                    $reference = $order->code;
                    $note      = $order->description;
                }
                break;
            case SupplierTransaction::OBJECT_DOCUMENT:
                $document               = $supplierTransaction->document;
                $documentSupTransaction = $document->documentSupplierTransaction;
                $reference              = $documentSupTransaction->transaction_code;
                $note                   = $documentSupTransaction->note;
                break;
        }

        return [
            'id' => $supplierTransaction->id,
            'tenant_id' => $supplierTransaction->tenant_id,
            'supplier_id' => $supplierTransaction->supplier_id,
            'type' => $supplierTransaction->type,
            'object_type' => $supplierTransaction->object_type,
            'object_id' => $supplierTransaction->object_id,
            'amount' => $supplierTransaction->amount,
            'metadata' => $supplierTransaction->metadata,
            'inventory_trans_id' => $supplierTransaction->inventory_trans_id,
            'inventory_m4_trans_id' => $supplierTransaction->inventory_m4_trans_id,
            'sold_trans_id' => $supplierTransaction->sold_trans_id,
            'sold_m4_trans_id' => $supplierTransaction->sold_m4_trans_id,
            'note' => $note,
            'reference' => $reference,
            'created_at' => $supplierTransaction->created_at,
            'updated_at' => $supplierTransaction->updated_at,
        ];
    }

}
