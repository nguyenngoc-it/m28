<?php

namespace Modules\Order\Jobs;

use App\Base\Job;
use Modules\Location\Models\Location;
use Modules\Location\Models\LocationShippingPartner;
use Modules\Order\Models\Order;

class UpdateLocationShippingPartnerJob extends Job
{
    /**
     * @var int
     */
    protected $orderId;

    /**
     * UpdateLocationShippingPartnerJob constructor
     *
     * @param int $orderId
     */
    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle()
    {
        $order = Order::find($this->orderId);
        /**
         * Trường hợp đơn tạo ra mà DVVC chưa được map vào với 1 thị trường nào đó thì tự động map theo thị trường của merchant
         */
        if(
            $order->shipping_partner_id && $order->merchant &&
            !LocationShippingPartner::query()->where('shipping_partner_id', $order->shipping_partner_id)->count()
        ) {
            $country = $order->merchant->getCountry();
            if($country instanceof Location) {
                $country->locationShippingPartners()->create([
                    'shipping_partner_id' => $order->shipping_partner_id
                ]);
            }
        }
    }
}
