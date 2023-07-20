<?php

namespace Modules\ImportHistory\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\ImportHistory\Models\ImportHistoryItem;

class ImportHistoryItemQuery extends ModelQueryFactory
{
    protected $joins = [
        'skus' => ['import_history_items.sku_id', '=', 'skus.id'],
    ];

    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new ImportHistoryItem();
    }

    /**
     * Filter theo kho
     * @param ModelQuery $query
     * @param $warehouseId
     */
    protected function applyWarehouseIdsFilter(ModelQuery $query, $warehouseId)
    {
        if (is_array($warehouseId)) {
            $query->getQuery()->whereIn('import_history_items.warehouse_id', $warehouseId);
        } else {
            $query->where('import_history_items.warehouse_id', $warehouseId);
        }
    }

    /**
     * @param ModelQuery $query
     * @param $code
     */
    protected function applySkuCodeFilter(ModelQuery $query, $code)
    {
        $query->join('skus')->where('skus.code', trim($code));
    }

    /**
     * @param ModelQuery $query
     * @param $code
     */
    protected function applySkuNameFilter(ModelQuery $query, $code)
    {
        $query->join('skus')->where('skus.name', 'LIKE', '%'.trim($code).'%');
    }
}
