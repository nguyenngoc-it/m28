<?php

namespace Modules\SupplierTransaction\Service;

use Modules\SupplierTransaction\Commands\ListSupplierTransaction;

class SupplierTransactionService implements SupplierTransactionInterface
{
    public function query(array $filter)
    {
        return (new SupplierTransactionQuery())->query($filter);
    }

    public function lists(array $filters)
    {
        return (new ListSupplierTransaction($filters))->handle();
    }

}
