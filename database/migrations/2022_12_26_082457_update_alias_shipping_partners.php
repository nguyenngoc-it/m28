<?php

use Illuminate\Database\Migrations\Migration;
use Modules\ShippingPartner\Models\ShippingPartner;
class UpdateAliasShippingPartners extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $shippingPartners = ShippingPartner::query()->whereNull('alias')->get();
        /** @var ShippingPartner $shippingPartner */
        foreach ($shippingPartners as $shippingPartner) {
            $shippingPartner->alias =  [$shippingPartner->code, strtolower($shippingPartner->code)];
            $shippingPartner->save();
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
