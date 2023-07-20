<?php

namespace Modules\ImportHistory\Transformers;

use App\Base\Transformer;
use Modules\ImportHistory\Models\ImportHistory;
use Modules\ImportHistory\Models\ImportHistoryItem;

class ImportHistoryListItemTransformer extends Transformer
{
    /**
     * Transform the data
     *
     * @param ImportHistoryItem $importHistoryItem
     * @return mixed
     */
    public function transform($importHistoryItem)
    {
        $sku      = $importHistoryItem->sku;
        $warehouse = $importHistoryItem->warehouse;
        $warehouseArea = $importHistoryItem->warehouseArea;

        return compact('sku', 'importHistoryItem', 'warehouse', 'warehouseArea');
    }
}
