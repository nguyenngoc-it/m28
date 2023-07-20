<?php

namespace Modules\Product\Events;

use App\Base\Event;
use Modules\Product\Models\SkuCombo;
use Modules\User\Models\User;

class SkuComboAttributesUpdated extends Event
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
    public $skuComboOriginal;

    /**
     * @var array
     */
    public $changedAttributes;

    /**
     * SkuComboAttributesUpdated constructor
     *
     * @param SkuCombo $skuCombo
     * @param User $creator
     * @param array $skuComboOriginal
     * @param array $changedAttributes
     */
    public function __construct(SkuCombo $skuCombo, User $creator, array $skuComboOriginal, array $changedAttributes)
    {
        $this->skuCombo          = $skuCombo;
        $this->creator           = $creator;
        $this->skuComboOriginal  = $skuComboOriginal;
        $this->changedAttributes = $changedAttributes;
    }
}
