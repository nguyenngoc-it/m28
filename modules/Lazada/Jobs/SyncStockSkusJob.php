<?php

namespace Modules\Lazada\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncStockSkusJob extends Job
{

    /**
     * @var Store
     */
    protected $store;

    /**
     * @var int
     */
    protected $merchantId;

    /**
     * @var bool
     */
    protected $filterUpdateTime = true;

    protected $type;

    /**
     * @param Store $store
     * @param $merchantId
     * @param $filterUpdateTime
     * @param $type
     */
    public function __construct(Store $store, $merchantId, $filterUpdateTime = null, $type)
    {
        $this->store = $store;
        $this->merchantId = $merchantId;
        $this->filterUpdateTime = $filterUpdateTime;
        $this->type = $type;
    }

    public function handle()
    {
        Service::lazada()->syncStockSkus($this->store, $this->merchantId, $this->filterUpdateTime, $this->type);
    }

}
