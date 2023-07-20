<?php

namespace Modules\KiotViet\Jobs;

use App\Base\Job;
use Gobiz\Support\RestApiException;
use Gobiz\Workflow\WorkflowException;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncKiotVietOrdersJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'kiotviet';

    /**
     * @var int
     */
    protected $shopId;

    /** @var Store $store */
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
     * SyncShopeeProductsJob constructor.
     * @param Store $store
     * @param $merchantId
     * @param bool $filterUpdateTime
     */
    public function __construct(Store $store, $merchantId, $filterUpdateTime = true)
    {
        $this->store = $store;
        $this->merchantId = $merchantId;
        $this->filterUpdateTime = $filterUpdateTime;
    }

    /**
     * @throws RestApiException
     * @throws WorkflowException
     */
    public function handle()
    {
        Service::kiotviet()->syncOrders($this->store);
    }
}
