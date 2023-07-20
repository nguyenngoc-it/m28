<?php

namespace Modules\Lazada\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncLazadaProductJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'lazada';

    /**
     * @var int
     */
    protected $store;

    /**
     * @var array
     */
    protected $lazadaItemIds;

    /**
     * SyncLazadaProductJob constructor.
     * @param int $storeId
     * @param array $lazadaItemIds
     */
    public function __construct( Store $store, $lazadaItemIds)
    {
        $this->store = $store;
        $this->lazadaItemIds = $lazadaItemIds;
    }

    public function handle()
    {
        Service::lazada()->syncProduct($this->store, $this->lazadaItemIds);
    }
}
