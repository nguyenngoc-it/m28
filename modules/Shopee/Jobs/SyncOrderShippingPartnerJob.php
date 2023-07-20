<?php

namespace Modules\Shopee\Jobs;

use App\Base\Job;
use Modules\Shopee\Commands\SyncOrderShippingPartner;

class SyncOrderShippingPartnerJob extends Job
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
    protected $orderInput;

    /**
     * SyncOrderShippingPartnerJob constructor
     *
     * @param int $storeId
     * @param array $orderInput
     */
    public function __construct($storeId, $orderInput)
    {
        $this->storeId = $storeId;
        $this->orderInput = $orderInput;
    }

    public function handle()
    {
        (new SyncOrderShippingPartner($this->storeId, $this->orderInput))->handle();
    }
}
