<?php

namespace App\Jobs;

use Gobiz\Queue\QueueConstant;

class EventJob extends \App\Base\Job
{
    protected $eventName;
    protected $payload;

    /**
     * EventJob constructor.
     * @param $eventName
     * @param array $payload
     */
    public function __construct($eventName, array $payload)
    {
        $this->eventName = $eventName;
        $this->payload   = $payload;
    }

    public $queue = QueueConstant::EVENT_QUEUES;

    public function handle()
    {
        event($this->eventName, $this->payload);
    }
}