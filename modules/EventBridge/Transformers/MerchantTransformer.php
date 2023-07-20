<?php

namespace Modules\EventBridge\Transformers;

use App\Base\Transformer;
use Modules\Merchant\Models\Merchant;

class MerchantTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Merchant $data
     * @return mixed
     */
    public function transform($data)
    {
        return $data->only(['id', 'code', 'name']);
    }
}
