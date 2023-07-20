<?php

namespace Modules\PurchasingOrder\Jobs;

use App\Base\Job;
use Exception;
use Modules\PurchasingOrder\Events\Subscribers\OrderPublicEventSubscriberM2;

class SubscribingM2OrderJob extends Job
{
    public $queue = 'm2_order_event';

    /**
     * @var array
     */
    protected $inputs = [];

    /**
     * HandleM2OrderEvent constructor
     *
     * @param array $inputs
     */
    public function __construct(array $inputs)
    {
        $this->inputs = $inputs;
    }

    /**
     * @throws Exception
     */
    public function handle()
    {
        (new OrderPublicEventSubscriberM2($this->inputs))->handle();
    }
}
