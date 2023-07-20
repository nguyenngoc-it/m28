<?php

namespace Modules\EventBridge\Jobs;

use App\Base\Job;
use Modules\Service;

class PutEventBridgeJob extends Job
{
    public $queue = 'aws';

    /**
     * @var array
     */
    protected $input;

    /**
     * PutEventBridgeJob constructor
     *
     * @param array $input
     */
    public function __construct(array $input)
    {
        $this->input = $input;
    }

    public function handle()
    {
        Service::eventBridge()->putEvents($this->input);
    }
}
