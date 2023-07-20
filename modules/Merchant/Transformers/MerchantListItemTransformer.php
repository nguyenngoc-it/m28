<?php

namespace Modules\Merchant\Transformers;

use App\Base\Transformer;
use Modules\Location\Models\Location;
use Modules\Merchant\Models\Merchant;
use Modules\User\Models\User;

class MerchantListItemTransformer extends Transformer
{

    /**
     * Transform the data
     *
     * @param Merchant $merchant
     * @return mixed
     */
    public function transform($merchant)
    {
        $location = $merchant->location;
        $currency = ($location instanceof Location) ? $location->currency : null;
        $user     = ($merchant->user instanceof User) ? $merchant->user->only(['id', 'username', 'email', 'name']) : null;
        return compact('merchant', 'location', 'currency', 'user');
    }
}
