<?php

namespace Modules\User\Transformers;

use App\Base\Transformer;
use Modules\User\Models\User;

class UserListTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param User $user
     * @return mixed
     */
    public function transform($user)
    {
        $merchants  = $user->merchants;
        $warehouses = $user->warehouses;
        $countries  = $user->locations;
        $suppliers  = $user->suppliers;

        return compact('user', 'merchants', 'warehouses', 'countries', 'suppliers');
    }
}
