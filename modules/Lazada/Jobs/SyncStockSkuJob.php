<?php

namespace Modules\Lazada\Jobs;

use App\Base\Job;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncStockSkuJob extends Job
{
    /**
     * @var Sku
     */
    protected $sku;

    protected $type;

    protected $store;

    public function __construct(Sku $sku, Store $store)
    {
        $this->sku = $sku;
        $this->store = $store;
    }

    public function handle()
    {
        Service::lazada()->syncStockSku($this->sku, $this->store);
    }

}
