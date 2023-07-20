<?php

namespace Modules\Supplier\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Supplier\Models\Supplier;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\Supplier\Services\SupplierEvent;

class CreateSupplier
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
     * CreateSupplier constructor.
     * @param User $creator
     * @param array $input
     */
    public function __construct(User $creator, array $input)
    {
        $this->creator = $creator;
        $this->input = $input;
    }


    /**
     * @return Supplier
     */
    public function handle()
    {
        $supplier = DB::transaction(function(){
            $tenant_id = $this->creator->tenant_id;
            $this->input['tenant_id'] = $tenant_id;
            $supplier = Supplier::create($this->input);

            $supplier->inventoryWallet()->create();
            $supplier->soldWallet()->create();

            return $supplier;
        });

        $supplier->logActivity(SupplierEvent::CREATE, $this->creator);

        return $supplier;
    }
}
