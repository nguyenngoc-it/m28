<?php

namespace Modules\Product\Listeners;

use App\Base\QueueableListener;
use Modules\Product\Events\SkuComboAttributesUpdated;
use Modules\Product\Services\SkuEvent;

class SkuComboAttributesUpdatedListener extends QueueableListener
{
    /**
     * @param SkuComboAttributesChanged $event
     */
    public function handle(SkuComboAttributesUpdated $event)
    {
        $skuCombo         = $event->skuCombo;
        $creator          = $event->creator;
        $skuComboOriginal = $event->skuComboOriginal;
        $changedAtts      = $event->changedAttributes;
        /**
         * Lưu log thay đổi thông tin sku combo
         */
        $payload = array_merge_recursive($skuComboOriginal, $changedAtts);
        if ($payload) {
            $skuCombo->logActivity(SkuEvent::SKU_COMBO_UPDATE, $creator, $payload, [
                'time' => $event->skuCombo->updated_at,
            ]);
        }
    }
}
