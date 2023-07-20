<?php

namespace Modules\KiotViet\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncKiotVietOrderJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'kiotviet';

    /**
     * @var int
     */
    protected $store;

    /**
     * @var array
     */
    protected $orderInputs;

    /**
     * SyncKiotVietOrdersJob constructor
     *
     * @param int $store
     * @param array $orderInputs
     */
    public function __construct(Store $store, array $orderInputs)
    {
        $this->store       = $store;
        $this->orderInputs = $orderInputs;
    }

    public function handle()
    {
        Service::kiotviet()->syncOrder($this->store, $this->orderInputs);
    }
}
