<?php

namespace Modules\KiotViet\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;

class UpdateTokenKiotVietJob extends Job
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
     * SyncKiotVietProductJob constructor.
     * @param Store $store
     * @param $kiotVietItemId
     */
    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    public function handle()
    {
        Service::kiotviet()->updateToken($this->store);
    }
}
