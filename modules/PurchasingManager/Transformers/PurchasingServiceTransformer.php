<?php

namespace Modules\PurchasingManager\Transformers;

use League\Fractal\TransformerAbstract;
use Modules\PurchasingManager\Models\PurchasingService;

class PurchasingServiceTransformer extends TransformerAbstract
{
    public function transform(PurchasingService $purchasingService)
    {
        return [
            'id'          => $purchasingService->id,
            'name'        => $purchasingService->name,
            'code'        => $purchasingService->code,
            'base_uri'    => $purchasingService->base_uri,
            'description' => $purchasingService->description,
            'active'      => $purchasingService->active
        ];
    }

}
