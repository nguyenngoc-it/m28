<?php

namespace Modules\Merchant\Transformers;

use App\Base\Transformer;
use Modules\Location\Models\Location;
use Modules\Merchant\Models\Merchant;
use Modules\Service;

class MerchantDetailTransformer extends Transformer
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
        return compact('merchant', 'location', 'currency');
    }
}
