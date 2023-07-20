<?php

namespace Modules\User\Commands;

use Modules\User\Models\User;
use Modules\User\Models\UserMerchant;
use Modules\User\Services\UserEvent;

class AddMerchant
{
    /**
     * @var array
     */
    protected $merchants;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var User
     */
    protected $creator;

    /**
     * AddMerchant constructor.
     * @param User $user
     * @param User $creator
     * @param array $merchants
     */
    public function __construct(User $user, User $creator, array $merchants = [])
    {
        $this->user = $user;
        $this->creator = $creator;
        $this->merchants = $merchants;
    }


    /**
     * @return User
     */
    public function handle()
    {
        $merchantsOld = $this->user->merchants()->pluck('name')->toArray();

        $merchantIds = [];
        $merchantsNew = [];
        foreach ($this->merchants as $merchant) {
            $merchantIds[] = $merchant->id;
            $merchantsNew[] = $merchant->name;
        }

        $this->user->merchants()->sync($merchantIds);

        $this->user->logActivity(UserEvent::ADD_MERCHANT, $this->creator, compact('merchantsNew', 'merchantsOld'));

        return $this->user;
    }
}