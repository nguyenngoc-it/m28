<?php

namespace Modules\Tiki\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncTikiProductJob extends Job
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
    protected $tikiItemIds;

    /**
     * SyncTikiProductJob constructor.
     * @param Store $store
     * @param array $tikiItemIds
     */
    public function __construct( Store $store, $tikiItemIds)
    {
        $this->store = $store;
        $this->tikiItemIds = $tikiItemIds;
    }

    public function handle()
    {
        Service::tiki()->syncProduct($this->store, $this->tikiItemIds);
    }
}
