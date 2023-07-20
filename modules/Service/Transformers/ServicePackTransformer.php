<?php

namespace Modules\Service\Transformers;

use App\Base\Transformer;
use Modules\Service\Models\ServicePack;

class ServicePackTransformer extends Transformer
{
    /**
     * @param ServicePack $data
     * @return array
     */
    public function transform($data): array
    {
        return [
            'id' => $data->id,
            'code' => $data->code,
            'name' => $data->name,
            'note' => $data->note,
            'created_at' => $data->created_at
        ];
    }
}
