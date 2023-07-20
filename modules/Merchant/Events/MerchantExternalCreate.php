<?php

namespace Modules\Merchant\Events;

use App\Base\Event;
use Modules\Merchant\Resource\DataResource;
use Modules\User\Models\User;

class MerchantExternalCreate extends Event
{

    public $user;
    public $dataResource;
    public function __construct(DataResource $dataResource, User $user)
    {
        $this->dataResource = $dataResource;
        $this->user = $user;
    }

}
