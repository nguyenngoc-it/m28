<?php

namespace Modules\TikTokShop\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncTikTokShopProductsJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'tiktokshop';

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
     * SyncTikTokShopProductsJob constructor.
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
        Service::tikTokShop()->syncProducts($this->store, $this->filterUpdateTime);
    }
}
