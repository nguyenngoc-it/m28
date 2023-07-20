<?php

namespace Modules\Product\Jobs;

use App\Base\Job;

class SkuEventJob extends Job
{
    /**
     * @var Object|string
     */
    protected $event;

    /**
     * @var array
     */
    protected $payload = [];

    /**
     * @var string
     */
    public $queue = 'sku_events';

    /**
     * Indicates whether the job should be dispatched after all database transactions have committed.
     *
     * @var bool|null
     */
    public $afterCommit = true;

    /**
     * EventJob constructor
     *
     * @param $event
     * @param array $payload
     */
    public function __construct($event, array $payload = [])
    {
        $this->event   = $event;
        $this->payload = $payload;
    }

    public function handle()
    {
        event($this->event, $this->payload);
    }
}
