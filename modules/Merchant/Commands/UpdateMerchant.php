<?php

namespace Modules\Merchant\Commands;

use Modules\Merchant\Models\Merchant;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\Merchant\Services\MerchantEvent;

class UpdateMerchant
{
    /**
     * @var array
     */
    protected $input;

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
     * UpdateMerchant constructor.
     * @param Merchant $merchant
     * @param User $creator
     * @param array $input
     */
    public function __construct(Merchant $merchant, User $creator, array $input)
    {
        $this->merchant = $merchant;
        $this->creator  = $creator;
        $this->input    = $input;
    }


    /**
     * @return Merchant
     */
    public function handle()
    {
        if (isset($this->input['code'])) {
            unset($this->input['code']);
        }

        $username = isset($this->input['username']) ? trim($this->input['username']) : "";
        if ($this->merchant->username != $username) {
            if (!empty($username)) {
                $user                    = User::query()->where('tenant_id', $this->creator->tenant_id)
                    ->where('username', $username)->first();
                $this->input['user_id']  = ($user instanceof User) ? $user->id : 0;
                $this->input['username'] = $username;
            } else {
                $this->input['user_id']  = 0;
                $this->input['username'] = null;
            }
        }

        $this->merchant->update($this->input);
        $this->merchant->logActivity(MerchantEvent::UPDATE, $this->creator, $this->merchant->getChanges());

        return $this->merchant;
    }
}
