<?php

namespace Modules\Product\Events;

use Carbon\Carbon;
use Modules\Product\Models\BatchOfGood;
use Modules\Product\Models\Sku;
use Modules\User\Models\User;

class BatchOfGoodCreated extends SkuEvent
{
    /** @var Sku */
    public $sku;
    /** @var BatchOfGood */
    public $batchOfGood;
    /** @var User */
    public $user;
    /** @var Carbon */
    public $carbon;

    /**
     * ProductCreated constructor.
     * @param Sku $sku
     * @param BatchOfGood $batchOfGood
     * @param User $user
     * @param Carbon $carbon
     */
    public function __construct(Sku $sku, BatchOfGood $batchOfGood, User $user, Carbon $carbon)
    {
        $this->sku         = $sku;
        $this->batchOfGood = $batchOfGood;
        $this->user        = $user;
        $this->carbon      = $carbon;
    }
}
