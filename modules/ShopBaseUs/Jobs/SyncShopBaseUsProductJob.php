<?php

namespace Modules\ShopBaseUs\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncShopBaseUsProductJob extends Job
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
     * @var array
     */
    protected $shopBaseUsItemId;

    /**
     * SyncShopBaseUsProductJob constructor.
     * @param Store $store
     * @param array $shopBaseUsItemId
     */
    public function __construct( Store $store, $shopBaseUsItemId)
    {
        $this->store = $store;
        $this->shopBaseUsItemId = $shopBaseUsItemId;
    }

    public function handle()
    {
        Service::shopBaseUs()->syncProduct($this->store, $this->shopBaseUsItemId);
    }
}
