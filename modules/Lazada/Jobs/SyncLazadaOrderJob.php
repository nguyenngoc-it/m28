<?php

namespace Modules\Lazada\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncLazadaOrderJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'lazada';

    /**
     * @var Store
     */
    protected $store;

    /**
     * @var array
     */
    protected $orderInputs;

    /**
     * SyncLazadaOrdersJob constructor
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
        Service::lazada()->syncOrder($this->store, $this->orderInputs);
    }
}
