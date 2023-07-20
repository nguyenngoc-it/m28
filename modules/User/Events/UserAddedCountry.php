<?php

namespace Modules\User\Events;

use App\Base\Event;
use Modules\User\Models\User;

class UserAddedCountry extends Event
{
    /** @var User $user */
    public $user;
    /** @var User $creator */
    public $creator;
    /** @var array $addedCountryIds */
    public $addedCountryIds = [];

    /**
     * OrderCreated constructor
     *
     * @param User $user
     * @param User $creator
     * @param array $addedCountryIds
     */
    public function __construct(User $user, User $creator, array $addedCountryIds = [])
    {
        $this->user            = $user;
        $this->creator         = $creator;
        $this->addedCountryIds = $addedCountryIds;
    }
}
