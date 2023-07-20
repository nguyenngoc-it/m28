<?php

namespace Modules\Order\Commands;

use Illuminate\Support\Arr;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderTransaction;
use Modules\Order\Services\OrderEvent;
use Modules\Service;
use Modules\User\Models\User;

class CreateOrderTransaction
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
     * @var bool
     */
    protected $changeCod = false;

    /**
     * CreateOrderTransaction constructor.
     * @param Order $order
     * @param $input
     * @param User $creator
     * @param bool $changeCod
     */
    public function __construct(Order $order, $input, User $creator, $changeCod = false)
    {
        $this->order     = $order;
        $this->input     = $input;
        $this->creator   = $creator;
        $this->changeCod = $changeCod;
    }

    /**
     * @return mixed|OrderTransaction|null|void
     */
    public function handle()
    {
        $orderTransaction = $this->makeOrderTransactions($this->order);
        if (empty($orderTransaction)) {
            return;
        }

        $this->collationOrder($orderTransaction);

        return $orderTransaction;
    }

    /**
     * @param Order $order
     * @return OrderTransaction|mixed|null
     */
    public function makeOrderTransactions(Order $order)
    {
        $paymentAmount = floatval(Arr::get($this->input, 'payment_amount', 0));
        $data          = [
            'tenant_id' => $order->tenant_id,
            'method' => $this->input['payment_method'] ?? '',
            'amount' => $paymentAmount,
            'bank_name' => isset($this->input['bank_name']) ? trim($this->input['bank_name']) : '',
            'bank_account' => isset($this->input['bank_account']) ? trim($this->input['bank_account']) : '',
            'note' => isset($this->input['payment_note']) ? trim($this->input['payment_note']) : '',
        ];

        $data['payment_time'] = (isset($this->input['payment_time'])) ? Service::order()->formatDateTime($this->input['payment_time']) : null;

        return $order->orderTransactions()->create($data);
    }

    /**
     * @param OrderTransaction $orderTransaction
     */
    protected function collationOrder(OrderTransaction $orderTransaction)
    {
        $this->order->paid_amount = $this->order->paid_amount + $orderTransaction->amount;
        if (isset($this->input['total_amount']) && $this->input['total_amount'] === 0) {
            $this->order->debit_amount = 0;
        } else {
            $this->order->debit_amount = $this->order->total_amount - $this->order->paid_amount;
        }
        if (
            $this->changeCod
            && $this->order->cod >= $orderTransaction->amount
        ) {
            $this->order->cod = $this->order->cod - $orderTransaction->amount;
        }

        if (
            $this->order->status == Order::STATUS_DELIVERED &&
            $this->order->debit_amount == 0
        ) {
            $this->order->logActivity(OrderEvent::CHANGE_STATUS, Service::user()->getSystemUserDefault(), [
                'old_status' => $this->order->status,
                'new_status' => Order::STATUS_FINISH
            ]);
            $this->order->status = Order::STATUS_FINISH;
        }

        $this->order->save();
    }
}
