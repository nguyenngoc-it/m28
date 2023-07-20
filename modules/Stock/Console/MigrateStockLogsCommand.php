<?php

namespace Modules\Stock\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Modules\Stock\Models\Stock;
use Modules\Stock\Models\StockLog;

class MigrateStockLogsCommand extends Command
{
    protected $signature = 'stock:migrate-logs {--batch=500}';

    protected $description = 'Migrate old stock logs';

    public function handle()
    {
        do {
            $logs = StockLog::query()
                ->select('stock_logs.*')
                ->join('stocks', 'stock_logs.stock_id', '=', 'stocks.id')
                ->whereNull('stock_logs.sku_id')
                ->limit($this->option('batch'))
                ->with(['stock'])
                ->get();

            foreach ($logs as $log) {
                $this->updateLog($log);
            }

            $this->info("Processed {$logs->count()} logs");
        } while ($logs->isNotEmpty());
    }

    protected function updateLog(StockLog $log)
    {
        $action = Arr::get([
            'ORDER' => Stock::ACTION_RESERVE,
            'CANCEL_ORDER' => Stock::ACTION_UNRESERVE,
            'REMOVE_STOCK_ORDER' => Stock::ACTION_UNRESERVE,
        ], $log->action);

        if ($action) {
            $log->action = $action;
        }

        $log->update(array_merge([
            'sku_id' => $log->stock->sku_id,
        ], $this->makeQuantityData($log)));

        $log = StockLog::find($log->id);
        $log->update(['sign' => $log->makeSign()]);
    }

    protected function makeQuantityData(StockLog $log)
    {
        switch ($log->action) {
            case Stock::ACTION_IMPORT:
            {
                return [
                    'change' => StockLog::CHANGE_INCREASE,
                    'quantity' => $log->quantity,
                    'real_quantity' => $log->quantity,
                ];
            }

            case Stock::ACTION_EXPORT:
            {
                return [
                    'change' => StockLog::CHANGE_DECREASE,
                    'quantity' => $log->quantity,
                    'real_quantity' => $log->quantity,
                ];
            }

            case Stock::ACTION_RESERVE:
            case Stock::ACTION_RESERVE_BY_ERROR:
            {
                return [
                    'change' => StockLog::CHANGE_DECREASE,
                    'quantity' => $log->quantity,
                    'real_quantity' => null,
                ];
            }

            case Stock::ACTION_EXPORT_FOR_ORDER:
            case Stock::ACTION_EXPORT_FOR_PICKING:
            {
                return [
                    'change' => StockLog::CHANGE_DECREASE,
                    'quantity' => null,
                    'real_quantity' => $log->quantity,
                ];
            }

            case Stock::ACTION_UNRESERVE:
            case Stock::ACTION_UNRESERVE_BY_ERROR:
            {
                return [
                    'change' => StockLog::CHANGE_INCREASE,
                    'quantity' => $log->quantity,
                    'real_quantity' => null,
                ];
            }

            case Stock::ACTION_IMPORT_FOR_PICKING:
            {
                return [
                    'change' => StockLog::CHANGE_INCREASE,
                    'quantity' => null,
                    'real_quantity' => $log->quantity,
                ];
            }

            default:
            {
                return [];
            }
        }
    }
}
