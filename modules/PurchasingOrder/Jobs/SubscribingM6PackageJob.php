<?php

namespace Modules\PurchasingOrder\Jobs;

use App\Base\Job;
use Modules\PurchasingOrder\Events\Subscribers\PackagePublicEventSubscriberM6;

class SubscribingM6PackageJob extends Job
{
    public $queue = 'm6_package_event';

    /**
     * @var array
     */
    protected $inputs = [];

    /**
     * SubscribingM6PackageJob constructor.
     * @param array $inputs
     */
    public function __construct(array $inputs)
    {
        $this->inputs = $inputs;
    }

    public function handle()
    {
        (new PackagePublicEventSubscriberM6($this->inputs))->handle();
    }
}
