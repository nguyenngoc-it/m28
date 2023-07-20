<?php

namespace Modules\Service\Transformers;


use League\Fractal\TransformerAbstract;
use Modules\Service\Models\ServicePrice;

class ServicePriceTransformer extends TransformerAbstract
{
    public function transform(ServicePrice $servicePrice)
    {
        return [
            'id' => (int)$servicePrice->id,
            'code' => $servicePrice->service_code,
            'label' => $servicePrice->label,
            'price' => $servicePrice->price,
        ];
    }
}
