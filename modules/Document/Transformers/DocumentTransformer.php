<?php

namespace Modules\Document\Transformers;

use App\Base\Transformer;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentFreightBillInventory;
use Modules\Document\Models\ImportingBarcode;
use Modules\Product\Models\Sku;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\PurchasingPackage\Transformers\PurchasingPackageDetailTransformer;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Models\TenantSetting;

class DocumentTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Document $document
     * @return mixed
     */
    public function transform($document)
    {
        $documentConfirm       = false;
        if($document->type == Document::TYPE_IMPORTING) {
            $tenant                = $document->tenant;
            $documentSetting       = $tenant->getSetting(TenantSetting::DOCUMENT_IMPORTING);
            if($documentSetting === true) {
                $documentSkuImportings = $document->documentSkuImportings;
                foreach ($documentSkuImportings as $documentSkuImporting) {
                    $sku = $documentSkuImporting->sku;
                    if ($sku) {
                        if ((
                            !$sku->width || !$sku->height || !$sku->length || !$sku->weight || !$sku->confirm_weight_volume)
                        ) {
                            $documentConfirm = true;
                        }
                    }
                }
            }
        }

        $remainingTotalAmount = 0;
        $codTotalAmount = 0;
        $totalAmount    = 0;
        if ($document->type == Document::TYPE_FREIGHT_BILL_INVENTORY) {
            $totalAmount  = $document->documentFreightBillInventories()
                ->sum(DB::raw('cod_paid_amount - cod_fee_amount - shipping_amount'));
            $codTotalAmount  = $document->documentFreightBillInventories()
                ->sum('cod_total_amount');
            $remainingTotalAmount  = $document->documentFreightBillInventories()
                ->sum(DB::raw('cod_total_amount - cod_paid_amount'));
        }

        $base                 = array_merge($document->attributesToArray(), [
            'warehouse_code' => $document->warehouse ? $document->warehouse->code : null,
            'warehouse_name' => $document->warehouse ? $document->warehouse->name : null,
            'shipping_partner_code' => $document->shippingPartner ? $document->shippingPartner->code : null,
            'shipping_partner_name' => $document->shippingPartner ? $document->shippingPartner->name : null,
            'creator_username' => $document->creator ? $document->creator->username : null,
            'verifier_username' => $document->verifier ? $document->verifier->username : null,
            'cod_total_amount' => $codTotalAmount != 0 ? $codTotalAmount : null,
            'remaining_total_amount' => $remainingTotalAmount ? $remainingTotalAmount : null,
            'currency' => $document->warehouse ? $document->warehouse->country->currency : null,
            'total_amount' => $totalAmount,
            'document_confirm' => $documentConfirm
        ]);

        $extras = [];
        if ($document->type == Document::TYPE_IMPORTING) {
            if ($document->importingBarcodes->count()) {
                /** @var ImportingBarcode $importingBarcode */
                foreach ($document->importingBarcodes as $importingBarcode) {
                    if (in_array($importingBarcode->type, [ImportingBarcode::TYPE_PACKAGE_CODE, ImportingBarcode::TYPE_PACKAGE_FREIGHT_BILL]) && $importingBarcode->object_id) {
                        $purchasingPackage = PurchasingPackage::find($importingBarcode->object_id);
                        if ($purchasingPackage instanceof PurchasingPackage) {
                            $extras['purchasing_packages'][] = (new PurchasingPackageDetailTransformer())->transform($purchasingPackage);
                        }
                    }
                }
            }
        } else {
            $extras['importing_barcodes'] = $document->importingBarcodes;
        }

        return array_merge($base, $extras);
    }
}
