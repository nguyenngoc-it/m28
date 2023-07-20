<?php

namespace Modules\Merchant\Events;

use App\Base\Event;
use Modules\Merchant\Models\Merchant;
use Modules\User\Models\User;

class MerchantCreated extends Event
{
    /** @var Merchant $merchant */
    public $merchant;
    /** @var User $creator */
    public $creator;

    /**
     * OrderCreated constructor
     *
     * @param Merchant $merchant
     * @param User $creator
     */
    public function __construct(Merchant $merchant, User $creator = null)
    {
        $this->merchant = $merchant;
        $this->creator  = $creator;
    }
}
