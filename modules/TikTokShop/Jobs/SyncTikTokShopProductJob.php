<?php

namespace Modules\TikTokShop\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncTikTokShopProductJob extends Job
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
    protected $tikTokShopItemId;

    /**
     * SyncTikTokShopProductJob constructor.
     * @param Store $store
     * @param array $tikTokShopItemId
     */
    public function __construct( Store $store, $tikTokShopItemId)
    {
        $this->store = $store;
        $this->tikTokShopItemId = $tikTokShopItemId;
    }

    public function handle()
    {
        Service::tikTokShop()->syncProduct($this->store, $this->tikTokShopItemId);
    }
}
