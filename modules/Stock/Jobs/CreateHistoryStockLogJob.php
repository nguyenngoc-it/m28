<?php

namespace Modules\Stock\Jobs;

use App\Base\Job;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Modules\Stock\Models\Stock;
use Modules\Stock\Models\StockLog;

class CreateHistoryStockLogJob extends Job
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
        /**
         * Nếu chưa được đồng bộ lại lịch sử lần nào thì phải đồng bộ trước
         */
        if (!$this->stock->logs()->sum('stock_logs.stock_quantity')) {
            dispatch(new SyncHistoryStockLogJob($this->stock));
            return;
        }

        $firstStock = 0;
        $stockLogs  = $this->stock->logs()->where('created_at', '>', Carbon::now()->subYear())
            ->orderBy('stock_logs.id', 'desc')->limit(100)->select('stock_logs.*')->get()
            ->sortBy('id');
        $i          = 0;
        /** @var StockLog $stockLog */
        foreach ($stockLogs as $stockLog) {
            if (empty($stockLog->stock_quantity)) {
                if (empty($i)) {
                    continue;
                }
                $stockLog->stock_quantity = $this->changeStockByAction($firstStock, $stockLog);
                $stockLog->save();
            }
            $firstStock = $stockLog->stock_quantity;
            $i++;
        }
    }

    /**
     * @param int $firstStock
     * @param StockLog $stockLog
     * @return int
     */
    protected function changeStockByAction(int $firstStock, StockLog $stockLog)
    {
        if ($stockLog->change == StockLog::CHANGE_INCREASE) {
            return $firstStock + (int)($stockLog->real_quantity);
        }
        if ($stockLog->change == StockLog::CHANGE_DECREASE) {
            return $firstStock - (int)($stockLog->real_quantity);
        }
        return $firstStock;
    }
}
