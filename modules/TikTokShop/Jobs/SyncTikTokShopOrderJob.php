<?php

namespace Modules\TikTokShop\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncTikTokShopOrderJob extends Job
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
     * @var array
     */
    protected $orderInputs;

    /**
     * SyncTikTokShopOrderJob constructor
     *
     * @param Store $store
     * @param array $orderInputs
     */
    public function __construct(Store $store, array $orderInputs)
    {
        $this->store       = $store;
        $this->orderInputs = $orderInputs;
    }

    public function handle()
    {
        Service::tikTokShop()->syncOrder($this->store, $this->orderInputs);
    }
}
