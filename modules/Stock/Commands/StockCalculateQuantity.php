<?php

namespace Modules\Stock\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Order\Models\Order;
use Modules\Stock\Models\Stock;
use Gobiz\Log\LogService;
use Psr\Log\LoggerInterface;

/**
 * Class StockCalculateQuantity
 * @package Modules\Stock\Commands
 */
class StockCalculateQuantity
{

    /**
     * Stock
     *
     * @var Stock $stock
     */
    protected $stock;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * StockCalculateQuantity constructor
     */
    public function __construct(Stock $stock)
    {
        $this->stock  = $stock;
        $this->logger = LogService::logger('stock-calculate-quantity', [
            'context' => ['stock_id' => $this->stock->id],
        ]);
    }

    /**
     * @return void
     */
    public function handle()
    {
        // Tính tổng số lượng tồn các đơn chưa xuất
        $reservedQuantity = Order::query()
            ->join('order_stocks', 'orders.id', 'order_stocks.order_id')
            ->whereIn('orders.status', [
                Order::STATUS_WAITING_CONFIRM,
                Order::STATUS_WAITING_PROCESSING,
                Order::STATUS_WAITING_PICKING,
                Order::STATUS_WAITING_PACKING,
                Order::STATUS_WAITING_DELIVERY,
            ])
            ->where('order_stocks.stock_id', $this->stock->id)
            ->sum('order_stocks.quantity');

        // Update tồn tạm tính
        $this->stock->update([
            'quantity' => DB::raw('real_quantity - ' . $reservedQuantity),
        ]);

        $this->logger->info('updated-stock-quantity', [
            'real_quantity' => $this->stock->real_quantity,
            'reserved_quantity' => $reservedQuantity,
        ]);
    }
}
