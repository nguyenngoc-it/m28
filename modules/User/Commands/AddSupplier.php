<?php

namespace Modules\User\Commands;

use Illuminate\Database\Eloquent\Collection;
use Modules\Supplier\Models\Supplier;
use Modules\User\Models\User;
use Modules\User\Services\UserEvent;

class AddSupplier
{
    /**
     * @var array
     */
    protected $suppliers;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var User
     */
    protected $creator;

    /**
     * AddSupplier constructor.
     * @param User $user
     * @param User $creator
     * @param Supplier[]|Collection $suppliers
     */
    public function __construct(User $user, User $creator, $suppliers)
    {
        $this->user = $user;
        $this->creator = $creator;
        $this->suppliers = $suppliers;
    }


    /**
     * @return User
     */
    public function handle()
    {
        $suppliersOld = $this->user->suppliers()->pluck('name')->toArray();
        $suppliersNew = $this->suppliers->pluck('name')->toArray();
        $this->user->suppliers()->sync($this->suppliers->pluck('id')->toArray());

        $this->user->logActivity(UserEvent::ADD_SUPPLIER, $this->creator, compact('suppliersNew', 'suppliersOld'));

        return $this->user;
    }
}
