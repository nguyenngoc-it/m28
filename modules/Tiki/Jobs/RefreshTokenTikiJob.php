<?php

namespace Modules\Tiki\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\Store\Models\Store;

class RefreshTokenTikiJob extends Job
{
    /**
     * @var string
     */
    public $queue = 'tiki';

    /**
     * @var Store
     */
    protected $store;

    /**
     * SyncTikiProductJob constructor.
     * @param Store $store
     * @param $TikiItemId
     */
    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    public function handle()
    {
        Service::tiki()->refreshToken($this->store);
    }
}
