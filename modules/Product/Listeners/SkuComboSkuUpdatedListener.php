<?php

namespace Modules\Product\Listeners;

use App\Base\QueueableListener;
use Modules\Product\Events\SkuComboSkuUpdated;
use Modules\Product\Models\Sku;
use Modules\Product\Services\SkuEvent;

class SkuComboSkuUpdatedListener extends QueueableListener
{
    /**
     * @param SkuComboSkuUpdated $event
     */
    public function handle(SkuComboSkuUpdated $event)
    {
        $skuCombo = $event->skuCombo;
        $creator  = $event->creator;
        $syncSkus = $this->detailSyncSkus($event->syncSkus);

        /**
         * Lưu log thay đổi thông tin skus
         */
        if (!empty($syncSkus)) {
            $skuCombo->logActivity(SkuEvent::SKU_COMBO_SKU_UPDATE, $creator, $syncSkus, [
                'time' => $event->skuCombo->updated_at,
            ]);
        }
    }

    /**
     * @param array $syncSkus [id => [quantity, discount_amount ...]]
     * @return array
     */
    protected function detailSyncSkus(array $syncSkus): array
    { 
        $listSkus = Sku::query()->whereIn('id', array_keys($syncSkus))->pluck('code', 'id')->toArray();
        foreach ($syncSkus as $skuId => $syncSku) {
            $syncSkus[$skuId]['sku_id']   = $skuId;
            $syncSkus[$skuId]['sku_code'] = !empty($listSkus[$skuId]) ? $listSkus[$skuId] : 'unknown';
        }
        return array_values($syncSkus);
    }
}
