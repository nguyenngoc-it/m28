<?php

namespace Modules\ImportHistory\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\ImportHistory\Models\ImportHistory;

class ImportHistoryQuery extends ModelQueryFactory
{
    protected $joins = [
        'import_history_items' => ['import_histories.id', '=', 'import_history_items.import_history_id'],
    ];

    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new ImportHistory();
    }


    /**
     * @param ModelQuery $query
     * @param $code
     */
    protected function applySkuIdFilter(ModelQuery $query, $code)
    {
        $query->join('import_history_items')
            ->where('import_history_items.sku_id', trim($code));
    }

    /**
     * @param ModelQuery $query
     * @param $code
     */
    protected function applyWarehouseAreaIdFilter(ModelQuery $query, $code)
    {
        $query->join('import_history_items')
            ->where('import_history_items.warehouse_area_id', trim($code));
    }

    /**
     * @param ModelQuery $query
     * @param $code
     */
    protected function applyWarehouseIdFilter(ModelQuery $query, $code)
    {
        $query->join('import_history_items')
            ->where('import_history_items.warehouse_id', trim($code));
    }

    /**
     * Filter theo kho
     * @param ModelQuery $query
     * @param $warehouseId
     */
    protected function applyWarehouseIdsFilter(ModelQuery $query, $warehouseId)
    {
        $query->join('import_history_items');
        if (is_array($warehouseId)) {
            $query->getQuery()->whereIn('import_history_items.warehouse_id', $warehouseId);
        } else {
            $query->where('import_history_items.warehouse_id', $warehouseId);
        }
    }

    /**
     * @param ModelQuery $query
     * @param $merchantId
     */
    protected function applyMerchantIdFilter(ModelQuery $query, $merchantId)
    {
        $query->join('import_history_items')
            ->where('import_history_items.merchant_id', intval($merchantId));
    }

    /**
     * Filter theo 	thoi gian tao
     *
     * @param ModelQuery $query
     * @param array $input
     */
    protected function applyCreatedAtFilter(ModelQuery $query, array $input)
    {
        $this->applyFilterTimeRange($query, 'import_histories.created_at', $input);
    }

}
