<?php

namespace Modules\Merchant\ExternalTransformers;

use App\Base\Transformer;
use Modules\Service\Models\Service;

class ServiceTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Service $service
     * @return array
     */
    public function transform($service)
    {
        return $service->only([
            'type',
            'code',
            'name',
            'status'
        ]);
    }
}
