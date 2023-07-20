<?php

namespace Modules\OrderPacking\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Order\Models\Order;
use Modules\OrderPacking\Events\OrderPackingCreated;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Service;

class CreateOrderPacking
{
    /**
     * @var Order|null
     */
    protected $order = null;

    /**
     * CancelOrder constructor.
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * @return OrderPacking|null
     */
    public function handle(): ?OrderPacking
    {
        if ($this->order->orderPacking) {
            return $this->order->orderPacking;
        }

        return DB::transaction(function () {
            $orderPacking = $this->createOrderPacking();
            Service::order()->updateOrderPackingItemsByOrder($this->order->refresh());
            (new OrderPackingCreated($orderPacking))->queue();
            return $this->order->orderPacking;
        });
    }

    /**
     * @return OrderPacking
     */
    private function createOrderPacking(): OrderPacking
    {
        $orderWarehouseStock = $this->order->getWarehouseStock();
        return OrderPacking::updateOrCreate(
            [
                'tenant_id' => $this->order->tenant_id,
                'merchant_id' => $this->order->merchant_id,
                'order_id' => $this->order->id,
            ],
            [
                'total_quantity' => $this->order->orderSkus->sum('quantity'),
                'total_values' => $this->order->orderSkus->sum('order_amount'),
                'warehouse_id' => $orderWarehouseStock ? $orderWarehouseStock->id : 0,
                'shipping_partner_id' => $this->order->shipping_partner_id ?: 0,
                'receiver_name' => $this->order->receiver_name,
                'receiver_phone' => $this->order->receiver_phone,
                'receiver_address' => $this->order->receiver_address,
                'payment_type' => $this->order->payment_type,
                'payment_method' => $this->order->payment_method,
                'intended_delivery_at' => $this->order->intended_delivery_at,
                'freight_bill_id' => 0,
            ]
        );
    }
}
