<?php

namespace Modules\Service\Transformers;

use App\Base\Transformer;
use Modules\Service\Models\ServiceCombo;

class ServiceComboTransformer extends Transformer
{
    /**
     * @param ServiceCombo $data
     * @return array
     */
    public function transform($data): array
    {
        return [
            'id' => $data->id,
            'service_pack_id' => $data->service_pack_id,
            'code' => $data->code,
            'name' => $data->name,
            'note' => $data->note,
            'using_days' => $data->using_days,
            'using_skus' => $data->using_skus,
            'suggest_price' => $data->suggest_price,
            'created_at' => $data->created_at
        ];
    }
}
