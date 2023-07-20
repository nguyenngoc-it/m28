<?php

namespace Modules\Order\Commands;

use Gobiz\Log\LogService;
use Modules\Order\Models\Order;
use Modules\User\Models\User;

class ChangeDeliveryFee
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var float
     */
    protected $deliveryFee;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @param Order $order
     * @param $deliveryFee
     * @param $creator
     */
    public function __construct(Order $order, $deliveryFee, $creator)
    {
        $this->order           = $order;
        $this->deliveryFee     = $deliveryFee;
        $this->creator         = $creator;
    }

    public function handle()
    {
        $order = $this->order;
        if(
            $order->delivery_fee != $this->deliveryFee &&
            $order->canChangeDeliveryFee()
        ) {
            LogService::logger('change_delivery_fee')->error('change delivery fee '.$order->code, [
                'from' => $order->delivery_fee,
                'to' => $this->deliveryFee,
                'user' => $this->creator->username
            ]);

            $order->delivery_fee = $this->deliveryFee;
            $order->total_amount = $order->order_amount + $order->shipping_amount + $order->delivery_fee - $order->discount_amount;
            $order->debit_amount = $order->total_amount - $order->paid_amount;
            $order->save();
        }
        return $order;
    }
}
