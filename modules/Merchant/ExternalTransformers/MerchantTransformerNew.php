<?php

namespace Modules\Merchant\ExternalTransformers;

use League\Fractal\TransformerAbstract;
use Modules\Location\Models\Location;
use Modules\Merchant\Models\Merchant;

class MerchantTransformerNew extends TransformerAbstract
{
    public function transform(Merchant $merchant)
    {
        $location = Location::query()->where('id', $merchant->location_id)->first();
        return [
            'id' => $merchant->id,
            'code' => $merchant->code,
            'name' => $merchant->name,
            'phone' => $merchant->phone,
            'address' => $merchant->address,
            'description' => $merchant->description,
            'location' => $location ? $location->label : '',
            'username' => $merchant->username,
            'status' => $merchant->status,
            'free_days_of_storage' => $merchant->free_days_of_storage,
        ];
    }

}
