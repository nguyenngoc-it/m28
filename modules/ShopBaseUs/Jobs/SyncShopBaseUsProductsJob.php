<?php

namespace Modules\ShopBaseUs\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncShopBaseUsProductsJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'shopbaseus';

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

    /**
     * SyncShopBaseUsProductsJob constructor.
     * @param $store
     * @param $merchantId
     * @param bool $filterUpdateTime
     */
    public function __construct(Store $store, $merchantId, $filterUpdateTime = false)
    {

        $this->store = $store;
        $this->merchantId = $merchantId;
        $this->filterUpdateTime = $filterUpdateTime;
    }

    public function handle()
    {
        Service::shopBaseUs()->syncProducts($this->store, $this->filterUpdateTime);
    }
}
