<?php

namespace Modules\Tiki\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncTikiOrdersJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'tiki';

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
     * SyncTikiOrdersJob constructor.
     * @param Store $store
     * @param int $merchantId
     * @param bool $filterUpdateTime
     */
    public function __construct(Store $store, $merchantId, $filterUpdateTime = true)
    {
        $this->store = $store;
        $this->merchantId = $merchantId;
        $this->filterUpdateTime = $filterUpdateTime;
    }

    public function handle()
    {
        Service::tiki()->syncOrders($this->store, $this->merchantId, $this->filterUpdateTime);
    }
}
