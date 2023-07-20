<?php

namespace Modules\Sapo\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncSapoOrderJob extends Job
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
     * @var array
     */
    protected $orderInputs;

    /**
     * SyncSapoOrderJob constructor
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
        Service::sapo()->syncOrder($this->store, $this->orderInputs);
    }
}
