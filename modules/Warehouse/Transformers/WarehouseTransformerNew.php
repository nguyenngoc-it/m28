<?php

namespace Modules\Warehouse\Transformers;


use League\Fractal\TransformerAbstract;
use Modules\Location\Transformers\LocationTransformer;
use Modules\Warehouse\Models\Warehouse;

class WarehouseTransformerNew extends TransformerAbstract
{

    public function __construct()
    {
        $this->setAvailableIncludes(['country']);
    }

    public function transform(Warehouse $warehouse)
    {
        return [
            'id' => $warehouse->id,
            'name' => $warehouse->name,
            'code' => $warehouse->code,
            'address' => $warehouse->address,
            'status' => $warehouse->status,
            'country_id' => $warehouse->country_id,
            'description' => $warehouse->description,
            'district_id' => $warehouse->district_id,
            'is_main' => $warehouse->is_main,
            'phone' => $warehouse->phone,
            'province_id' => $warehouse->province_id,
            'settings' => $warehouse->settings,
            'tenant_id' => $warehouse->tenant_id,
            'ward_id' => $warehouse->ward_id,
            'created_at' => $warehouse->created_at,
            'updated_at' => $warehouse->updated_at
        ];
    }
    /**
     * Include Creator
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeCountry(Warehouse $warehouse)
    {
        $country = $warehouse->country;
        if ($country) {
            return $this->item($country, new LocationTransformer);
        }else
            return $this->null();

    }
}
