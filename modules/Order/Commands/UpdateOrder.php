<?php
namespace Modules\Order\Commands;

use Modules\Location\Models\Location;
use Modules\Order\Models\Order;
use Modules\Order\Services\OrderEvent;
use Modules\User\Models\User;

class UpdateOrder
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var array
     */
    protected $input;

    /**
     * @var User|null
     */
    protected $creator = null;

    /**
     * UpdateOrder constructor.
     * @param Order $order
     * @param array $input
     * @param User $creator
     */
    public function __construct(Order $order, array $input = [], User $creator)
    {
        $this->order = $order;
        $this->input = $input;
        $this->creator  = $creator;
    }

    public function handle()
    {
        $logData = [];
        foreach ($this->input as $key => $value) {
            if(
                in_array($key, Order::$updateOrderParams) &&
                isset($this->order->{$key}) && $this->order->{$key} != $value
            ) {
                if(in_array($key, ['receiver_province_id', 'receiver_district_id', 'receiver_ward_id'])) {
                    $locationNew = Location::find($value);
                    if($key == 'receiver_province_id') {
                        $locationOld = $this->order->receiverProvince;
                    } else if($key == 'receiver_district_id') {
                        $locationOld = $this->order->receiverDistrict;
                    } else {
                        $locationOld = $this->order->receiverWard;
                    }
                    $key = str_replace('_id', '', $key);
                    $logData[$key] = ['old' => $locationOld ? $locationOld->label : '', 'new' => $locationNew->label];
                } else {
                    $logData[$key] = ['old' => $this->order->{$key}, 'new' => $value];
                }
            }
        }

        $this->order->update($this->input);

        $this->order->logActivity(OrderEvent::UPDATE, $this->creator, $logData);

        return $this->order;
    }
}
