<?php

namespace Modules\Service\Transformers;

use App\Base\Transformer;
use Modules\Service\Models\ServicePackPrice;

class ServicePackPriceTransformer extends Transformer
{
    /**
     * @param ServicePackPrice $data
     * @return array
     */
    public function transform($data): array
    {
        return [
            'id' => $data->id,
            'service_price_id' => $data->service_price_id,
            'created_at' => $data->created_at
        ];
    }
}
