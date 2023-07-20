<?php

namespace Modules\Product\Events;

use App\Base\Event;
use Modules\Product\Models\SkuCombo;
use Modules\User\Models\User;

class SkuComboSkuUpdated extends Event
{
    /**
     * @var SkuCombo
     */
    public $skuCombo;

    /**
     * @var User
     */
    public $creator;

    /**
     * @var array
     */
    public $syncSkus;

    /**
     * SkuComboSkuUpdated constructor
     *
     * @param SkuCombo $skuCombo
     * @param User $creator
     * @param array $syncSkus
     */
    public function __construct(SkuCombo $skuCombo, User $creator, array $syncSkus)
    {
        $this->skuCombo = $skuCombo;
        $this->creator  = $creator;
        $this->syncSkus = $syncSkus;
    }
}
