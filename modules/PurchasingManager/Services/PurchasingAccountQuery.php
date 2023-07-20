<?php

namespace Modules\PurchasingManager\Services;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\PurchasingManager\Models\PurchasingAccount;

class PurchasingAccountQuery extends ModelQueryFactory
{
    /**
     * Khởi tạo model
     */
    protected function newModel()
    {
        return new PurchasingAccount();
    }

    /**
     * Filter theo danh sách ids được chọn
     * @param ModelQuery $query
     * @param $ids
     */
    protected function applyIdsFilter(ModelQuery $query, $ids)
    {
        if (!empty($ids)) {
            $query->getQuery()->whereIn('purchasing_accounts.id', (array)$ids);
        }
    }

}
