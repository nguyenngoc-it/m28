<?php

namespace Modules\Order\Commands;

use Modules\Order\Models\Order;
use Modules\Order\Services\OrderEvent;
use Modules\Service;
use Modules\User\Models\User;

class UpdateOrderSKU
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var array
     */
    protected $orderSkuData;

    /**
     * @var User|null
     */
    protected $creator = null;

    /**
     * UpdateOrderSKU constructor.
     * @param Order $order
     * @param array $orderSkus
     * @param User $creator
     */
    public function __construct(Order $order, User $creator, array $orderSkus = [])
    {
        $this->order        = $order;
        $this->orderSkuData = $orderSkus;
        $this->creator      = $creator;
    }

    public function handle()
    {
        $logData   = [];
        $orderSkus = $this->order->orderSkus;
        foreach ($orderSkus as $orderSku) {
            if (isset($this->orderSkuData[$orderSku->sku_id])) {
                $skuData  = $this->orderSkuData[$orderSku->sku_id];
                $quantity = intval($skuData['quantity']);
                if ($quantity == $orderSku->quantity) {
                    continue;
                }

                $logData[] = ['sku_code' => $skuData['sku_code'], 'old' => $orderSku->quantity, 'new' => $quantity];

                $orderAmount = $orderSku->price * $quantity;
                $totalAmount = ($orderAmount + ($orderAmount * floatval($orderSku->tax) * 0.01)) - $orderSku->discount_amount;

                $data = [
                    'quantity' => $quantity,
                    'order_amount' => $orderAmount,
                    'total_amount' => $totalAmount,
                ];
                $orderSku->update($data);
            }
        }

        $orderAmount = $this->order->orderSkus()->sum('total_amount');
        $totalAmount = $orderAmount + $this->order->shipping_amount - $this->order->discount_amount;

        $this->order->total_amount = $totalAmount;
        $this->order->order_amount = $orderAmount;

        $paidAmount                = $this->order->orderTransactions()->sum('amount');
        $this->order->paid_amount  = $paidAmount;
        $this->order->debit_amount = $this->order->total_amount - $this->order->paid_amount;

        $this->order->save();

        $this->order->logActivity(OrderEvent::UPDATE_SKUS, $this->creator, $logData);
        /**
         * Bỏ chọn kho xuất trên đơn
         */
        Service::order()->removeStockOrder($this->order, $this->creator);
        return $this->order;
    }
}
