<?php

namespace Modules\Shopee\Jobs;

use App\Base\Job;
use Gobiz\Support\RestApiException;
use Modules\Marketplace\Services\MarketplaceException;
use Modules\Service;

class SyncShopeeProductJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'shopee';

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var array
     */
    protected $shopeeItemIds;

    /**
     * SyncShopeeProductJob constructor.
     * @param int $storeId
     * @param array $shopeeItemIds
     */
    public function __construct($storeId, $shopeeItemIds)
    {
        $this->storeId = $storeId;
        $this->shopeeItemIds = $shopeeItemIds;
    }

    /**
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function handle()
    {
        Service::shopee()->syncProduct($this->storeId, $this->shopeeItemIds);
    }
}
