<?php

namespace Modules\ImportHistory\Services;

use Gobiz\ModelQuery\ModelQuery;
use Modules\User\Models\User;

interface ImportHistoryServiceInterface
{
    /**
     * Khởi tạo đối tượng query sku
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function importHistoryQuery(array $filter);

    /**
     * Khởi tạo đối tượng query
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function importHistoryItemQuery(array $filter);

    /**
     * @param array $filter
     * @param User $user
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function listImportHistories(array $filter, User $user);
}
