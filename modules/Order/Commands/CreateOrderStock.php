<?php

namespace Modules\Order\Commands;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Order\Events\OrderStockCreated;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderSku;
use Modules\Order\Models\OrderStock;
use Modules\OrderPacking\Models\OrderPackingItem;
use Modules\Stock\Models\Stock;
use Modules\User\Models\User;


class CreateOrderStock
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var Stock
     */
    protected $stock;

    /**
     * @var integer
     */
    protected $quantity;

    /**
     * @var User|null
     */
    protected $creator = null;

    /**
     * CreateOrderStock constructor.
     * @param Order $order
     * @param Stock $stock
     * @param $quantity
     * @param User $creator
     */
    public function __construct(Order $order, Stock $stock, $quantity, User $creator)
    {
        $this->order    = $order;
        $this->stock    = $stock;
        $this->quantity = $quantity;
        $this->creator  = $creator;
    }

    /**
     * @return Builder|Model|OrderStock|object
     */
    public function handle()
    {
        $order    = $this->order;
        $stock    = $this->stock;
        $quantity = $this->quantity;
        $creator  = $this->creator;

        $orderStock = OrderStock::query()->where([
            'order_id' => $order->id,
            'stock_id' => $stock->id,
        ])->first();

        if ($orderStock) {
            // Nếu số tồn hold
            $stock = Stock::find($orderStock->stock_id);
            if ($stock) {
                $totalQuantity = (int) ($quantity + $orderStock->quantity);
                if ($totalQuantity > $stock->quantity) {
                    // $orderStock->quantity = 0;
                    $orderStock->delete();
                } else {
                    $orderStock->quantity += $quantity;
                    $orderStock->save();
                }
            }
            return $orderStock;
        }

        $orderStock = OrderStock::create([
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'stock_id' => $stock->id,
            'sku_id' => $stock->sku_id,
            'warehouse_id' => $stock->warehouse_id,
            'warehouse_area_id' => $stock->warehouse_area_id,
            'quantity' => $quantity,
            'creator_id' => $creator->id,
            'changing_stock_id' => 0,
        ]);

        (new OrderStockCreated($orderStock, $creator))->queue();

        return $orderStock;
    }

    /**
     * @param OrderStock $orderStock
     */
    protected function updateOrderPackingItems(OrderStock $orderStock)
    {
        /** @var OrderSku $orderSku */
        $orderSku = $this->order->orderSkus()->where('sku_id', $orderStock->sku_id)->first();

        //bỏ thông tin snapshot của stock trong OrderPackingItem
        OrderPackingItem::query()->where('sku_id', $orderStock->sku_id)
            ->where('order_id', $orderStock->order_id)
            ->where('order_stock_id', 0)
            ->update(
                [
                    'price' => $orderSku->price,
                    'warehouse_id' => $orderStock->warehouse_id,
                    'stock_id' => $orderStock->stock_id,
                    'order_stock_id' => $orderStock->id,
                    'warehouse_area_id' => $orderStock->warehouse_area_id,
                    'quantity' => $orderStock->quantity,
                    'values' => round($orderStock->quantity * $orderSku->price, 6)
                ]
            );
    }

}
