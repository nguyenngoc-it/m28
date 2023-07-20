<?php

namespace Modules\SupplierTransaction\Service;

use Gobiz\ModelQuery\ModelQuery;
use Gobiz\ModelQuery\ModelQueryFactory;
use Modules\SupplierTransaction\Models\SupplierTransaction;

class SupplierTransactionQuery extends ModelQueryFactory
{

    protected function newModel()
    {
        return new SupplierTransaction();
    }

    protected function applySupplierIdFilter(ModelQuery $query, $supplierId)
    {
        if (is_array($supplierId)) {
            $query->getQuery()->whereIn('supplier_transactions.supplier_id', $supplierId);
        } else {
            $query->where('supplier_transactions.supplier_id', $supplierId);
        }

    }


}
