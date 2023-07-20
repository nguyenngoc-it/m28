<?php

namespace Modules\PurchasingPackage\Transformers;

use App\Base\Transformer;
use Modules\Document\Models\Document;
use Modules\Document\Models\ImportingBarcode;
use Modules\Product\Models\Sku;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\PurchasingPackage\Models\PurchasingPackageService;

class PurchasingPackageTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param PurchasingPackage $purchasingPackage
     * @return array
     */
    public function transform($purchasingPackage)
    {
        $purchasingPackageItems = $purchasingPackage->purchasingPackageItems;
        $images = [];
        foreach ($purchasingPackageItems as $purchasingPackageItem) {
            $purchasingPackageItem->sku = $purchasingPackageItem->sku()->first(['id', 'product_id', 'name', 'code', 'barcode']);
            if(empty($images) && $purchasingPackageItem->sku instanceof Sku) {
                $product = $purchasingPackageItem->sku->product;
                if(!empty($product->images)) {
                    $images = $product->images;
                }
            }
        }

        $importingBarcode = $purchasingPackage->importingBarcodes()
            ->join('documents', 'importing_barcodes.document_id', '=', 'documents.id')
            ->where('documents.status', '!=', Document::STATUS_CANCELLED)
            ->whereIn('importing_barcodes.type', [ImportingBarcode::TYPE_PACKAGE_CODE, ImportingBarcode::TYPE_PACKAGE_FREIGHT_BILL])
            ->first();

        $document = ($importingBarcode instanceof ImportingBarcode) ? $importingBarcode->document->only(['id', 'code']) : null;

        return [
            'purchasing_package' => $purchasingPackage->attributesToArray(),
            'purchasing_package_items' => $purchasingPackageItems,
            'destination_warehouse' => $purchasingPackage->destinationWarehouse,
            'merchant'=> $purchasingPackage->merchant,
            'images' => $images,
            'document' => $document,
            'purchasing_package_services' => $purchasingPackage->purchasingPackageServices->map(function(PurchasingPackageService $purchasingPackageService){
                return [
                    'purchasing_package_service' => $purchasingPackageService,
                    'service' => $purchasingPackageService->service,
                    'service_price' => $purchasingPackageService->servicePrice,
                ];
            })
        ];
    }
}
