<?php

namespace Modules\DeliveryNote\Transformers;

use App\Base\Transformer;
use Modules\DeliveryNote\Models\DeliveryNote;
use Modules\Product\Models\Sku;
use Modules\Stock\Models\Stock;
use Modules\Stock\Models\StockLog;
use Modules\Warehouse\Models\WarehouseArea;

class DeliveryNoteDetailTransformer extends Transformer
{
    /**
     * @param DeliveryNote $deliveryNote
     * @return array|mixed
     */
    public function transform($deliveryNote)
    {
        $creator = $deliveryNote->creator()->first(['id', 'username', 'name', 'email', 'phone', 'avatar']);
        return [
            'deliveryNote' => $deliveryNote,
            'warehouse' => $deliveryNote->warehouse,
            'creator' => $creator,
            'skus' => $this->makeDeliverySkus($deliveryNote),
        ];
    }

    /**
     * @param DeliveryNote $deliveryNote
     * @return array|array[]
     */
    public function makeDeliverySkus(DeliveryNote $deliveryNote)
    {
        return StockLog::query()
            ->where('object_type', StockLog::OBJECT_DELIVERY_NOTE)
            ->where('action', Stock::ACTION_EXPORT)
            ->where('object_id', $deliveryNote->id)
            ->get()
            ->map(function ($stockLogs) {
                $payload = $stockLogs->payload;
                $warehouseArea = WarehouseArea::find($payload['warehouse_area_id']);
                return [
                    'sku' => Sku::find($payload['sku_id']),
                    'warehouseArea' => $warehouseArea,
                    'quantity' => $payload['quantity'],
                ];
            });
    }
}
