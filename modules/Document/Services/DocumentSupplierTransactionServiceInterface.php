<?php

namespace Modules\Document\Services;
use Modules\Document\Models\Document;
use Modules\Supplier\Models\Supplier;
use Modules\User\Models\User;

interface DocumentSupplierTransactionServiceInterface
{

    /**
     * @param Supplier $supplier
     * @param array $data
     * @param User $user
     * @return Document
     */
    public function create(Supplier $supplier, array $data, User $user): Document;

    /**
     * @param array $filter
     * @param User $user
     * @return array
     */
    public function listing(array $filter, User $user);
}
