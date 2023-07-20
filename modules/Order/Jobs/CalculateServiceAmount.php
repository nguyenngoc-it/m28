<?php

namespace Modules\Order\Jobs;

use App\Base\Job;
use Modules\Order\Models\Order;
use Modules\OrderPacking\Models\OrderPacking;

class CalculateServiceAmount extends Job
{
    public $connection = 'redis';

    public $queue = 'order_calculate_amount';

    /**
     * @var int
     */
    protected $orderId;

    /**
     * CalculateServiceAmount constructor
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
        if($order->dropship) return; //nếu đơn dropship thì tính theo bảng giá từ lúc tạo đơn rồi

        $order->service_amount = $order->orderPackings()->whereNotIn('status', [OrderPacking::STATUS_CANCELED])->sum('service_amount');
        $order->save();
    }
}
