<?php

namespace Modules\Stock\Events;

use App\Base\Event;
use Modules\Stock\Models\Stock;
use Modules\Stock\Models\StockLog;

class StockChanged extends Event
{
    /**
     * @var Stock
     */
    public $stock;

    /**
     * @var StockLog
     */
    public $stockLog;

    /**
     * StockChanged constructor
     *
     * @param Stock $stock
     * @param StockLog $stockLog
     */
    public function __construct(Stock $stock, StockLog $stockLog)
    {
        $this->stock = $stock;
        $this->stockLog = $stockLog;
    }
}
