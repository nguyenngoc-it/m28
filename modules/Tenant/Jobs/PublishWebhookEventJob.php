<?php

namespace Modules\Tenant\Jobs;

use App\Base\Job;
use Gobiz\Support\RestApiException;
use Modules\Service;

class PublishWebhookEventJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'webhook';

    /**
     * @var int
     */
    protected $tenantCode;

    /**
     * @var array
     */
    protected $event;

    /**
     * PublishWebhookEventJob constructor
     *
     * @param string $tenantCode
     * @param array $event
     */
    public function __construct($tenantCode, array $event)
    {
        $this->tenantCode = $tenantCode;
        $this->event = $event;
    }

    /**
     * @throws RestApiException
     */
    public function handle()
    {
        Service::app()->webhook()->publishEvent($this->tenantCode, $this->event);
    }
}
