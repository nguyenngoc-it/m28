<?php

namespace Modules\Stock\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Stock\Models\Stock;

class UpdateTemporaryStockJob extends Job
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
        Service::stock()->calculateQuantity($this->stock);
    }
}
