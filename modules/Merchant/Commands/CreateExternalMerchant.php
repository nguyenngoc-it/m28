<?php

namespace Modules\Merchant\Commands;

use Modules\Location\Models\Location;
use Modules\Merchant\Events\MerchantCreated;
use Modules\Merchant\Models\Merchant;
use Modules\Merchant\Resource\DataResource;
use Modules\Merchant\Services\MerchantEvent;
use Modules\Service;
use Modules\User\Models\User;

class CreateExternalMerchant
{
    public $inputs;
    public $user;

    public function __construct(array $inputs, User $user)
    {
        $this->inputs = $inputs;
        $this->user   = $user;
    }

    public function handle()
    {
        $location                           = data_get($this->inputs, 'location');
        $location                           = Location::query()->where('code', $location)->first();
        $dataResource                       = new DataResource();
        $dataResource->tenant_id            = data_get($this->inputs, 'tenant_id');
        $dataResource->creator_id           = data_get($this->inputs, 'creator_id');
        $dataResource->username             = data_get($this->inputs, 'user_name');
        $dataResource->code                 = data_get($this->inputs, 'code');
        $dataResource->name                 = data_get($this->inputs, 'name');
        $dataResource->phone                = data_get($this->inputs, 'phone');
        $dataResource->location_id          = $location ? $location->id : '';
        $dataResource->status               = data_get($this->inputs, 'status');
        $dataResource->description          = data_get($this->inputs, 'description');
        $dataResource->address              = data_get($this->inputs, 'address');
        $dataResource->free_days_of_storage = data_get($this->inputs, 'free_days_of_storage');
        $dataResource->ref                  = data_get($this->inputs, 'ref');
        $dataResource->storaged_at          = data_get($this->inputs, 'storaged_at');
        $dataResource->user_id              = data_get($this->inputs, 'user_id');
        $dataResource->warning_out_money    = data_get($this->inputs, 'warning_out_money');

        $merchant = Service::merchant()->createMerchantExternal($dataResource, $this->user);

        return $merchant;
    }

}
