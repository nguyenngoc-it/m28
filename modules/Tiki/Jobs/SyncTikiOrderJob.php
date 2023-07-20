<?php

namespace Modules\Tiki\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncTikiOrderJob extends Job
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
     * @var array
     */
    protected $orderInputs;

    /**
     * SyncTikiOrdersJob constructor
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
        Service::tiki()->syncOrder($this->store, $this->orderInputs);
    }
}
