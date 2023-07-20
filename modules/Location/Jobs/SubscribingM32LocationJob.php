<?php

namespace Modules\Location\Jobs;

use App\Base\Job;
use Modules\Location\Listeners\Kafka\LocationPublicEventSubscriberM32;

class SubscribingM32LocationJob extends Job
{
    public $queue = 'm32_location_event';

    /**
     * @var array
     */
    protected $inputs = [];

    /**
     * SubscribingM2LocationJob constructor.
     * @param array $inputs
     */
    public function __construct(array $inputs)
    {
        $this->inputs = $inputs;
    }

    public function handle()
    {
        (new LocationPublicEventSubscriberM32($this->inputs))->handle();
    }
}
