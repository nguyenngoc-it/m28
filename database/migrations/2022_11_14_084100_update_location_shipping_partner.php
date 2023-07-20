<?php

use Illuminate\Database\Migrations\Migration;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Location\Models\Location;
class UpdateLocationShippingPartner extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $code = 'other_3pl';
        $shippingPartner = ShippingPartner::create([
            'tenant_id' => 1,
            'code' => $code,
            'name' => 'Other-3PL',
            'alias' => [$code],
        ]);

        $countries = Location::query()->where('type', Location::TYPE_COUNTRY)->get();
        /** @var Location $country */
        foreach ($countries as $country) {
            $country->locationShippingPartners()->create([
                'shipping_partner_id' => $shippingPartner->id
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
