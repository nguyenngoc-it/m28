<?php

namespace Modules\PurchasingPackage\Transformers;

use App\Base\Transformer;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\PurchasingPackage\Models\PurchasingPackageItem;
use Modules\PurchasingPackage\Models\PurchasingPackageService;

class PurchasingPackageDetailTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param PurchasingPackage $purchasingPackage
     * @return array
     */
    public function transform($purchasingPackage)
    {
        $services = [];
        if($purchasingPackage->purchasingPackageServices->count()){
            /** @var PurchasingPackageService $purchasingPackageService */
            foreach ($purchasingPackage->purchasingPackageServices as $purchasingPackageService){
                $services[$purchasingPackageService->service_id]['service'] = $purchasingPackageService->service->only(['id','code','name','type']);
                $services[$purchasingPackageService->service_id]['service_prices'][] = array_merge($purchasingPackageService->servicePrice->only(['label']), $purchasingPackageService->only(['id','price','quantity', 'amount', 'skus']));
            }
        }
        return [
            'purchasing_package' => $purchasingPackage->attributesToArray(),
            'purchasing_package_items' => $purchasingPackage->purchasingPackageItems->transform(function (PurchasingPackageItem $purchasingPackageItem) {
                $sku      = ($purchasingPackageItem->sku ? $purchasingPackageItem->sku->only(['id','code', 'name']) : []);
                $product  = ($purchasingPackageItem->sku ? $purchasingPackageItem->sku->product->only(['id','code', 'name', 'images', 'image']) : []);

                return array_merge($purchasingPackageItem->toArray(), $sku, ['product' => $product]);
            }),
            'destination_warehouse' => $purchasingPackage->destinationWarehouse,
            'services' => array_values($services),
            'merchant' => $purchasingPackage->merchant,
        ];
    }
}
