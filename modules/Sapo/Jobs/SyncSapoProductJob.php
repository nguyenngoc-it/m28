<?php

namespace Modules\Sapo\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncSapoProductJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'sapo';

    /**
     * @var Store
     */
    protected $store;

    /**
     * @var array
     */
    protected $sapoItemId;

    /**
     * SyncSapoProductJob constructor.
     * @param Store $store
     * @param array $sapoItemId
     */
    public function __construct( Store $store, $sapoItemId)
    {
        $this->store = $store;
        $this->sapoItemId = $sapoItemId;
    }

    public function handle()
    {
        Service::sapo()->syncProduct($this->store, $this->sapoItemId);
    }
}
