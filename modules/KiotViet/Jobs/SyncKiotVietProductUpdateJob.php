<?php

namespace Modules\KiotViet\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncKiotVietProductUpdateJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'kiotviet';

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var integer
     */
    protected $KiotVietItemId;

    /**
     * SyncKiotVietProductJob constructor.
     * @param int $storeId
     * @param int $KiotVietItemId
     */
    public function __construct(Store $store, $KiotVietItemId)
    {
        $this->store          = $store;
        $this->KiotVietItemId = $KiotVietItemId;
    }

    public function handle()
    {
        Service::kiotviet()->syncProductUpdate($this->store, $this->KiotVietItemId);
    }
}
