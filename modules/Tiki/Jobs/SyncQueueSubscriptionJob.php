<?php

namespace Modules\Tiki\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncQueueSubscriptionJob extends Job
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
     * SyncTikiOrdersJob constructor
     *
     * @param Store $store
     * @param array $orderInputs
     */
    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    public function handle()
    {
        Service::tiki()->syncQueueSubscription($this->store);
    }
}
