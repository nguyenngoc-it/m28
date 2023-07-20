<?php

use Illuminate\Database\Migrations\Migration;
use Modules\ShippingPartner\Models\ShippingPartner;
class AddLweShippingPartners extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $shippingPartners = ShippingPartner::query()
            ->where('code', 'LWE')->get();

        /** @var  ShippingPartner $shippingPartner */
        foreach ($shippingPartners as $shippingPartner) {
            foreach (['LWE-LBC', 'LWE-JNT'] as $partner) {
                $settings = ['carrier' => $partner, 'connect_code' => $partner];
                ShippingPartner::create([
                    'tenant_id' => $shippingPartner->tenant_id,
                    'code' => $partner,
                    'name' => $partner,
                    'provider' => $shippingPartner->provider,
                    'settings' => $settings,
                    'alias' => [$partner, strtolower($partner)],
                    'temp_tracking' => $shippingPartner->temp_tracking,
                    'status' => $shippingPartner->status
                ]);
            }
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
