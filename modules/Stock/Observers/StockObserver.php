<?php

namespace Modules\Stock\Observers;

use Modules\Merchant\Models\Merchant;
use Modules\Stock\Models\Stock;
use Modules\WarehouseStock\Jobs\CalculateWarehouseStockJob;

class StockObserver
{
    public $afterCommit = true;

    /**
     * Handle to the OrderPacking "created" event.
     *
     * @param Stock $stock
     * @return void
     */
    public function created(Stock $stock)
    {
        /**
         * Gán ngày lưu kho miên phí
         */
        $seller = $stock->product->merchant;
        if (empty($seller) || !is_null($seller->free_days_of_storage)) {
            return;
        }
        $seller->free_days_of_storage = Merchant::FREE_DAYS_OF_STORAGE;
        $seller->save();

        dispatch(new CalculateWarehouseStockJob($stock->sku_id, $stock->warehouse_id));
    }

    /**
     * @param Stock $stock
     * @return void
     */
    public function updated(Stock $stock)
    {
        dispatch(new CalculateWarehouseStockJob($stock->sku_id, $stock->warehouse_id));
    }
}
