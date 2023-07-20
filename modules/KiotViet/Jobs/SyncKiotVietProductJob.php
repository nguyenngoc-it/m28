<?php

namespace Modules\KiotViet\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncKiotVietProductJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'kiotviet';

    /**
     * @var Store
     */
    protected $store;

    /**
     * @var integer
     */
    protected $kiotVietItemId;

    /**
     * SyncKiotVietProductJob constructor.
     * @param Store $store
     * @param $kiotVietItemId
     */
    public function __construct(Store $store, $kiotVietItemId)
    {
        $this->store          = $store;
        $this->kiotVietItemId = $kiotVietItemId;
    }

    public function handle()
    {
        Service::kiotviet()->syncProduct($this->store, $this->kiotVietItemId);
    }
}
