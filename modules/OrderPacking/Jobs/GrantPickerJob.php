<?php

namespace Modules\OrderPacking\Jobs;

use App\Base\Job;
use Modules\Service;
use Modules\User\Models\User;

class GrantPickerJob extends Job
{
    public $queue = 'grant_picker';

    protected $filter;
    protected $user;
    protected $picker;

    /**
     * GrantPickerJob constructor.
     * @param array $filter
     * @param $pickerId
     * @param $userId
     */
    public function __construct(array $filter, $pickerId, $userId)
    {
        $this->filter = $filter;
        $this->user   = User::find($userId);
        $this->picker = User::find($pickerId);
    }

    public function handle()
    {
        $orderPackings = Service::orderPacking()->listing($this->filter);
        Service::orderPacking()->grantPicker($orderPackings, $this->picker, $this->user);
    }
}
