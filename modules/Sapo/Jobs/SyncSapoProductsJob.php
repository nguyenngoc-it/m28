<?php

namespace Modules\Sapo\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncSapoProductsJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'sapo';

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
     * SyncSapoProductsJob constructor.
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
        Service::sapo()->syncProducts($this->store, $this->filterUpdateTime);
    }
}
