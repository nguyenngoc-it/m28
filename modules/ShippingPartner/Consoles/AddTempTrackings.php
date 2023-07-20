<?php

namespace Modules\ShippingPartner\Consoles;

use Illuminate\Console\Command;
use Modules\ShippingPartner\Models\ShippingPartner;

class AddTempTrackings extends Command
{
    protected $signature = 'shipping_partner:add_temp_tracking';

    protected $description = 'Add temp tracking';

    public function handle()
    {
        $tempTrackingLWE    = [
            [
                'col' => 'Tracking Number',
                'val' => ''
            ],
            ['col' => 'Partner Tracking Number', 'val' => ''],
            ['col' => 'Client Reference Number', 'val' => 'orders.code'],
            ['col' => 'Consignee Name', 'val' => 'orders.receiver_name'],
            ['col' => 'Address', 'val' => 'orders.receiver_address'],
            ['col' => 'Consignee Number', 'val' => 'orders.receiver_phone'],
            ['col' => 'Shipper', 'val' => ''],
            ['col' => 'Item Description', 'val' => 'remark'],
            ['col' => 'Mode Of Payment', 'val' => 'COD'],
            ['col' => 'Package Code', 'val' => 'N-PACK SMALL'],
            ['col' => 'Destination', 'val' => 'MMA'],
            ['col' => 'Quantity', 'val' => 'order_packings.total_quantity'],
            ['col' => 'Actual Weight', 'val' => 'weight'],
            ['col' => 'L', 'val' => ''],
            ['col' => 'W', 'val' => ''],
            ['col' => 'H', 'val' => ''],
            ['col' => 'Declared Value', 'val' => 'orders.cod'],
            ['col' => 'COD Amount', 'val' => 'orders.cod'],
            ['col' => 'Pay Mode', 'val' => 'COLLECT SHIPPER'],
            ['col' => 'Service Mode', 'val' => ''],
            ['col' => 'Instructions', 'val' => ''],
            ['col' => 'Partner', 'val' => ''],
            ['col' => 'Municipality', 'val' => 'district'],
            ['col' => 'Province', 'val' => 'province'],
            ['col' => 'Package Type', 'val' => ''],
            ['col' => 'Delivery Category', 'val' => '']
        ];
        $lwe                = ShippingPartner::query()->where('code', 'LWE')->first();
        $lwe->temp_tracking = $tempTrackingLWE;
        $lwe->save();
        $this->info('added temp trackings for LWE.');

        $tempTrackingJNTP    = [
            [
                'col' => 'Receiver(*)',
                'val' => 'orders.receiver_name'
            ],
            ['col' => 'Receiver Telephone (*)', 'val' => 'orders.receiver_phone'],
            ['col' => 'Receiver Address (*)', 'val' => 'orders.receiver_address'],
            ['col' => 'Receiver Province (*)', 'val' => 'province'],
            ['col' => 'Receiver City (*)', 'val' => 'district'],
            ['col' => 'Receiver Region (*)', 'val' => 'ward'],
            ['col' => 'Express Type (*)', 'val' => 'EZ'],
            ['col' => 'Parcel Name (*)', 'val' => 'orders.code'],
            ['col' => 'Weight (kg)  (*)', 'val' => 'weight'],
            ['col' => 'Number of Items(*)', 'val' => 'order_packings.total_quantity'],
            ['col' => 'Parcel Value (Insurance Fee) (*)', 'val' => 'orders.cod'],
            ['col' => 'COD (PHP) (*)', 'val' => 'orders.cod'],
            ['col' => 'Remarks', 'val' => 'remark'],
        ];
        $jntp                = ShippingPartner::query()->where('code', 'JNTP')->first();
        $jntp->temp_tracking = $tempTrackingJNTP;
        $jntp->save();
        $this->info('added temp trackings for JNTP.');
    }

}
