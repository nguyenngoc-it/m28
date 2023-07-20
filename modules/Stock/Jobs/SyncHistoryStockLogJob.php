<?php

namespace Modules\Stock\Jobs;

use App\Base\Job;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Modules\Stock\Models\Stock;
use Modules\Stock\Models\StockLog;

class SyncHistoryStockLogJob extends Job
{
    public $connection = 'redis';
    public $queue = 'stocks';

    /**
     * @var Stock
     */
    protected $stock;

    /**
     * @param Stock $stock
     */
    public function __construct(Stock $stock)
    {
        $this->stock = $stock;
    }

    public function handle()
    {
        $lastStockLog = $this->stock->logs()->orderBy('stock_logs.id', 'desc')->first();
        if ($lastStockLog instanceof StockLog) {
            $lastStockLog->stock_quantity = $this->stock->real_quantity;
            $lastStockLog->save();
        }
        $i                = 0;
        $lastStock        = 0;
        $lastRealQuantity = 0;
        $lastAction       = StockLog::CHANGE_INCREASE;
        $stockLogs        = StockLog::query()->where('stock_id', $this->stock->id)
            ->where('created_at', '>', Carbon::now()->subMonths(2))
            ->orderBy('id', 'desc')->get();
        /** @var StockLog $stockLog */
        foreach ($stockLogs as $stockLog) {
            if (empty($stockLog->stock_quantity) || ($i > 0)) {
                $stockLog->stock_quantity = $this->changeStockByAction($lastStock, $lastRealQuantity, $lastAction);
                $stockLog->save();
            }
            $lastStock        = (int)$stockLog->stock_quantity;
            $lastRealQuantity = (int)$stockLog->real_quantity;
            $lastAction       = $stockLog->change;
            $i++;
        }
    }

    /**
     * @param int $lastStock
     * @param int $lastRealQuantity
     * @param string $lastAction
     * @return int
     */
    protected function changeStockByAction(int $lastStock, int $lastRealQuantity, string $lastAction)
    {
        if ($lastAction == StockLog::CHANGE_INCREASE) {
            return $lastStock - $lastRealQuantity;
        }
        if ($lastAction == StockLog::CHANGE_DECREASE) {
            return $lastStock + $lastRealQuantity;
        }
        return $lastStock;
    }
}
