<?php

namespace Modules\Product\Events;

use Carbon\Carbon;
use Modules\Product\Models\Sku;
use Modules\User\Models\User;

class SkuIsGoodsBatchUpdated extends SkuEvent
{
    /** @var Sku */
    public $sku;
    /** @var User */
    public $user;
    /** @var Carbon */
    public $carbon;
    /** @var array */
    public $payload;

    /**
     * @param Sku $sku
     * @param User $user
     * @param Carbon $carbon
     * @param array $payload
     */
    public function __construct(Sku $sku, User $user, Carbon $carbon, array $payload = [])
    {
        $this->sku     = $sku;
        $this->user    = $user;
        $this->carbon  = $carbon;
        $this->payload = $payload;
    }
}
