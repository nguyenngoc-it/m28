<?php

namespace Modules\EventBridge\Transformers;

use App\Base\Transformer;
use Modules\Tenant\Models\Tenant;

class TenantTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Tenant $data
     * @return mixed
     */
    public function transform($data)
    {
        return $data->only(['id', 'code']);
    }
}
