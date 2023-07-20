<?php

namespace Modules\Service\Transformers;

use App\Base\Transformer;
use Modules\Service\Models\Service;

class ServiceListItemTransformer extends Transformer
{

    /**
     * Transform the data
     *
     * @param Service $service
     * @param bool $hidden_init_service
     * @return mixed
     */
    public function transform($service, bool $hidden_init_service = false)
    {
        $builder       = $service->servicePrices();
        $servicePrices = $builder->orderBy('price')->get();
        if ($servicePrices->count() == 0 && $hidden_init_service) {
            return null;
        }
        return compact('service', 'servicePrices');
    }
}
