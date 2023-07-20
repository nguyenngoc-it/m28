<?php
namespace Modules\Order\Commands;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderTransaction;
use Modules\Order\Services\OrderEvent;
use Modules\Service;
use Modules\User\Models\User;

class PaymentConfirm
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
     * PaymentConfirm constructor.
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
        $orderTransaction = $this->makeOrderTransaction($this->order);

        $this->order->logActivity(OrderEvent::PAYMENT_CONFIRM, $this->creator, $orderTransaction->attributesToArray());

        return $this->order;
    }



    /**
     * @param Order $order
     * @return OrderTransaction
     */
    public function makeOrderTransaction(Order $order)
    {
        return (new CreateOrderTransaction($order, $this->input, $this->creator, true))->handle();
    }
}
