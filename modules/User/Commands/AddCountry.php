<?php

namespace Modules\User\Commands;

use Illuminate\Support\Arr;
use Modules\User\Events\UserAddedCountry;
use Modules\User\Models\User;

class AddCountry
{
    /**
     * @var array
     */
    protected $countryIds = [];

    /**
     * @var User
     */
    protected $user;

    /**
     * @var User
     */
    protected $creator;

    /**
     * AddWarehouse constructor.
     * @param User $user
     * @param User $creator
     * @param array $inputs
     */
    public function __construct(User $user, User $creator, array $inputs = [])
    {
        $this->user       = $user;
        $this->creator    = $creator;
        $this->countryIds = Arr::get($inputs, 'country_ids', []);
    }


    /**
     * @return User
     */
    public function handle()
    {
        $oldCountryIds = $this->user->locations->pluck('id')->all();
        $this->user->locations()->sync($this->countryIds);
        $addedCountryIds = array_diff($this->user->locations()->pluck('locations.id')->all(), $oldCountryIds);
        (new UserAddedCountry($this->user, $this->creator, $addedCountryIds))->queue();
        return $this->user;
    }
}
