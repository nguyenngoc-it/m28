<?php

namespace Modules\Merchant\Commands;

use Modules\Merchant\Models\Merchant;
use Modules\Merchant\Resource\DataResource;
use Modules\Merchant\Services\MerchantEvent;
use Modules\User\Models\User;

class CreateExternalMerchantFrom3
{

    /** DataResource
     * @var
     */
    public $dataResource;
    /** User
     * @var
     */
    public $user;

    public function __construct(DataResource $dataResource, User $user)
    {
        $this->dataResource = $dataResource;
        $this->user         = $user;
    }

    public function handle()
    {
        $merchant = Merchant::create(
            [
                'tenant_id' => $this->dataResource->tenant_id,
                'creator_id' => $this->dataResource->creator_id,
                'username' => $this->dataResource->username,
                'name' => $this->dataResource->name,
                'phone' => $this->dataResource->phone,
                'code' => $this->dataResource->code,
                'location_id' => $this->dataResource->location_id,
                'status' => $this->dataResource->status,
                'address' => $this->dataResource->address,
                'description' => $this->dataResource->description,
                'user_id' => $this->dataResource->user_id,
                'ref' => $this->dataResource->ref,
                'storaged_at' => $this->dataResource->storaged_at,
                'free_days_of_storage' => $this->dataResource->free_days_of_storage,
                'warning_out_money' => $this->dataResource->warning_out_money
            ]
        );
        $merchant->logActivity(MerchantEvent::CREATE, $this->user);
        return $merchant;
    }
}
