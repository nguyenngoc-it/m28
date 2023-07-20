<?php

namespace Modules\Location\Transformers;


use League\Fractal\TransformerAbstract;
use Modules\Location\Models\Location;
use Modules\Order\Models\Order;
use Modules\User\Models\User;
use Modules\User\Transformers\UserTransformerNew;
use Modules\Warehouse\Models\Warehouse;

class LocationTransformer extends TransformerAbstract
{
    public function transform(Location $location)
    {
        return [
            'name' => $location->label,
        ];
    }
}
