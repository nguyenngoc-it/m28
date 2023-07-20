<?php

namespace Modules\PurchasingPackage\Transformers;

use League\Fractal\TransformerAbstract;
use Modules\PurchasingPackage\Models\PurchasingPackageService;
use Modules\Service\Transformers\ServicePriceTransformer;
use Modules\Service\Transformers\ServiceTransformer;

class PurchasingPackageServiceTransformer extends TransformerAbstract
{
    public function __construct()
    {
        $this->setAvailableIncludes(['service', 'service_price']);
    }

    public function transform(PurchasingPackageService $purchasingPackageService)
    {
        return [
            'id' => $purchasingPackageService->id,
            'price' => $purchasingPackageService->price,
            'quantity' => $purchasingPackageService->quantity,
            'amount' => $purchasingPackageService->amount,
            'skus' => $purchasingPackageService->skus
        ];
    }

    public function includeService(PurchasingPackageService $purchasingPackageService)
    {
        $service = $purchasingPackageService->service;
        if ($service) {
            return $this->item($service, new ServiceTransformer());
        }else
            return $this->null();
    }

    public function includeServicePrice(PurchasingPackageService $purchasingPackageService)
    {
        $servicePrice = $purchasingPackageService->servicePrice;
        if (!$servicePrice) {
            return $this->item($servicePrice, new ServicePriceTransformer());
        }else
            return $this->null();
    }
}
