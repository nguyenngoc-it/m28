<?php

namespace Modules\Merchant\ExternalTransformers;

use App\Base\Transformer;
use Modules\Merchant\Models\Merchant;

class MerchantTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param Merchant $merchant
     * @return mixed
     */
    public function transform($merchant)
    {
        return $merchant->only([
            'name',
            'code',
            'username',
            'phone',
            'address',
            'description',
            'status',
            'ref',
            'storaged_at',
            'free_days_of_storage',
            'created_at',
            'updated_at',
        ]);
    }
}
