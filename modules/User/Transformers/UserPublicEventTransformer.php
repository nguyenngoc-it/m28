<?php

namespace Modules\User\Transformers;

use App\Base\Transformer;
use Illuminate\Support\Arr;
use Modules\User\Models\User;

class UserPublicEventTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param User $user
     * @return mixed
     */
    public function transform($user)
    {
        return Arr::except($user->attributesToArray(), ['permissions', 'synced_at']);
    }
}
