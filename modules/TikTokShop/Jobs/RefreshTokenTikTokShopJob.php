<?php

namespace Modules\TikTokShop\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;

class RefreshTokenTikTokShopJob extends Job
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
     * SyncTikTokShopProductJob constructor.
     * @param Store $store
     * @param $TikTokShopItemId
     */
    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    public function handle()
    {
        Service::tikTokShop()->refreshToken($this->store);
    }
}
