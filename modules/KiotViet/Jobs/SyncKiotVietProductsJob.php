<?php

namespace Modules\KiotViet\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncKiotVietProductsJob extends Job
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
     * @var int
     */
    protected $merchantId;

    /**
     * @var bool
     */
    protected $filterUpdateTime = true;

    /**
     * SyncKiotVietProductsJob constructor.
     * @param $store
     * @param $merchantId
     * @param bool $filterUpdateTime
     */
    public function __construct(Store $store, $merchantId, $filterUpdateTime = true)
    {
        $this->store = $store;
        $this->merchantId = $merchantId;
        $this->filterUpdateTime = $filterUpdateTime;
    }

    public function handle()
    {
        Service::kiotviet()->syncProducts($this->store, $this->merchantId, $this->filterUpdateTime);
    }
}
