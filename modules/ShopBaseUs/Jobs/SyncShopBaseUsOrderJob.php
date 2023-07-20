<?php

namespace Modules\ShopBaseUs\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncShopBaseUsOrderJob extends Job
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
    protected $orderInputs;

    /**
     * SyncShopBaseUsOrderJob constructor
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
        Service::shopBaseUs()->syncOrder($this->store, $this->orderInputs);
    }
}
