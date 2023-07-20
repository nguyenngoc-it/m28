<?php

namespace Modules\User\Transformers;

use App\Base\Transformer;
use Modules\Service;
use Modules\User\Models\User;

class UserTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param User $user
     * @return mixed
     */
    public function transform($user)
    {
        $tenant = Service::tenant()->find($user->tenant_id);

        return array_merge($user->attributesToArray(), [
            'avatar' => $user->avatar ? $tenant->storage()->url($user->avatar) : 'https://www.gravatar.com/avatar/'.md5($user->username),
        ]);
    }
}
