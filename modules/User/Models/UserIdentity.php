<?php

namespace Modules\User\Models;

use App\Base\Model;

class UserIdentity extends Model
{
    protected $table = 'user_identities';

    protected $casts = [
        'source_user_info' => 'json',
    ];

    const SOURCE_GOBIZ = 'gobiz';
}