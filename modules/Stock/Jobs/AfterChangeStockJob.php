<?php

namespace Modules\Stock\Jobs;

use App\Base\Job;
use Modules\Product\Models\Sku;


class AfterChangeStockJob extends Job
{
    /**
     * @var integer
     */
    protected $skuId;

    /**
     * AfterChangeStockJob constructor.
     * @param $skuId
     */
    public function __construct($skuId)
    {
        $this->skuId = $skuId;
    }

    public function handle()
    {
        $sku = Sku::find($this->skuId);
        $sku->update([
                'stock' => $sku->stocks()->sum('quantity'),
                'real_stock' => $sku->stocks()->sum('real_quantity')
            ]);
    }
}
