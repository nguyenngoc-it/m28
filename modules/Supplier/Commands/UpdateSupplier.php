<?php

namespace Modules\Supplier\Commands;

use Illuminate\Support\Arr;
use Modules\Supplier\Models\Supplier;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\Supplier\Services\SupplierEvent;

class UpdateSupplier
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
    protected $supplier;

    /**
     * UpdateSupplier constructor.
     * @param Supplier $supplier
     * @param User $creator
     * @param array $input
     */
    public function __construct(Supplier $supplier, User $creator, array $input)
    {
        $this->supplier = $supplier;
        $this->creator = $creator;
        $this->input = $input;
    }


    /**
     * @return Supplier
     */
    public function handle()
    {
        $this->supplier->update($this->input);

        $this->supplier->logActivity(SupplierEvent::UPDATE, $this->creator, $this->supplier->getChanges());

        return $this->supplier;
    }
}
