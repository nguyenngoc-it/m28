<?php

namespace Modules\Order\Jobs;

use App\Base\Job;
use Modules\Order\Models\Order;

class CalculateAmountPaidToSeller extends Job
{
    public $connection = 'redis';

    public $queue = 'order_calculate_amount';

    /**
     * @var int
     */
    protected $orderId;

    /**
     * CalculateAmountPaidToSeller constructor
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
        if ($order) {
            $order->amount_paid_to_seller = $order->paid_amount
                - $order->service_amount
                - $order->cod_fee_amount
                - $order->shipping_amount
                - $order->cost_price
                - $order->cost_of_goods
                - $order->other_fee;
            $order->save();
        }
    }
}
