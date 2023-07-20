<?php

namespace Modules\Topship\Jobs;

use App\Base\Job;
use Modules\Service;

class SyncTopshipFreightBillJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'topship';

    /**
     * @var array
     */
    protected $fulfillment;

    /**
     * SyncTopshipFreightBillJob constructor
     *
     * @param array $fulfillment
     */
    public function __construct(array $fulfillment)
    {
        $this->fulfillment = $fulfillment;
    }

    public function handle()
    {
        Service::topship()->syncFreightBill($this->fulfillment);
    }
}
