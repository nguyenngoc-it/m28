<?php

namespace Modules\Merchant\Commands;

use Illuminate\Support\Arr;
use Modules\Merchant\Models\Merchant;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\Merchant\Services\MerchantEvent;

class ChangeStateMerchant
{
    /**
     * @var boolean
     */
    protected $status;

    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @var User
     */
    protected $merchant;

    /**
     * ChangeStateMerchant constructor.
     * @param Merchant $merchant
     * @param User $creator
     * @param $status
     */
    public function __construct(Merchant $merchant, User $creator, $status)
    {
        $this->merchant = $merchant;
        $this->creator = $creator;
        $this->status = $status;
    }


    /**
     * @return Merchant
     */
    public function handle()
    {
        if($this->merchant->status == $this->status) {
            return $this->merchant;
        }
        $this->merchant->status = $this->status;
        $this->merchant->save();

        $this->merchant->logActivity(MerchantEvent::CHANGE_STATE, $this->creator, $this->merchant->getChanges());

        return $this->merchant;
    }
}