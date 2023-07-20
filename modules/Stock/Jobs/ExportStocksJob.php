<?php

namespace Modules\Stock\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\User\Models\User;

class ExportStocksJob extends Job
{
    public $connection = 'redis';
    public $queue = 'stocks';

    protected $filter = [];
    /**
     * @var int
     */
    protected $batch = 100;

    /**
     * @var int
     */
    protected $creatorId;

    /**
     * ExportStocksJob constructor.
     * @param array $filter
     * @param $creatorId
     */
    public function __construct(array $filter = [], $creatorId)
    {
        $this->filter = $filter;
        $this->creatorId = $creatorId;
    }

    public function handle()
    {
        $creator = User::find($this->creatorId);
        return Service::stock()->export($this->filter, $creator);
    }
}
