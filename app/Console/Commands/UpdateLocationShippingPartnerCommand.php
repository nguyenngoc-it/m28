<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Location\Models\Location;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Models\ShippingPartner;

class UpdateLocationShippingPartnerCommand extends Command
{
    protected $signature = 'update-location-shipping-partner';

    protected $description = 'Update location shipping partner';

    /**
     * Cập nhật lại những đơn vị vận chuyển mà chưa được gắn vào thị trường nào
     */
    public function handle()
    {
        $shippingPartners = ShippingPartner::query()->select(['shipping_partners.*', 'location_shipping_partners.shipping_partner_id'])
            ->leftJoin('location_shipping_partners', 'location_shipping_partners.shipping_partner_id', '=', 'shipping_partners.id')
            ->whereNull('location_shipping_partners.shipping_partner_id')
            ->get();

        foreach ($shippingPartners as $shippingPartner) {
            $order = Order::query()->where('shipping_partner_id', $shippingPartner->id)
                ->where('merchant_id', '>', 0)
                ->with(['merchant'])
                ->first();
            if(!$order instanceof Order) {
                $this->info('Not found order');
                continue;
            }
            $merchant = $order->merchant;
            $country  = $merchant->getCountry();
            if(!$country instanceof Location) {
                $this->info('Not found location');
                continue;
            }

            $country->locationShippingPartners()->create([
                'shipping_partner_id' => $shippingPartner->id
            ]);
        }
    }
}