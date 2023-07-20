<?php

namespace Modules\OrderExporting\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\OrderExporting\Models\OrderExporting;

class OrderExportingQuery extends ModelQueryFactory
{
    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new OrderExporting();
    }

    /**
     * Filter theo danh sách ids được chọn
     * @param ModelQuery $query
     * @param $ids
     */
    protected function applyIdsFilter(ModelQuery $query, $ids)
    {
        if (!empty($ids)) {
            $query->getQuery()->whereIn('order_exportings.id', (array)$ids);
        }
    }

    /**
     * Filter theo trạng thái
     * @param ModelQuery $query
     * @param $status
     */
    protected function applyStatusFilter(ModelQuery $query, $status)
    {
        if (is_array($status)) {
            $query->getQuery()->whereIn('order_exportings.status', $status);
        } else {
            $query->where('order_exportings.status', $status);
        }
    }
}


