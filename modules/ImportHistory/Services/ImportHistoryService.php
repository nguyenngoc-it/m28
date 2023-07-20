<?php

namespace Modules\ImportHistory\Services;

use Gobiz\ModelQuery\ModelQuery;
use Modules\ImportHistory\Commands\ListImportHistories;
use Modules\User\Models\User;

class ImportHistoryService implements ImportHistoryServiceInterface
{
    /**
     * Khởi tạo đối tượng query
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function importHistoryQuery(array $filter)
    {
        return (new ImportHistoryQuery())->query($filter);
    }

    /**
     * Khởi tạo đối tượng query
     *
     * @param array $filter
     * @return ModelQuery
     */
    public function importHistoryItemQuery(array $filter)
    {
        return (new ImportHistoryItemQuery())->query($filter);
    }

    /**
     * @param array $filter
     * @param User $user
     * @return \Illuminate\Pagination\LengthAwarePaginator|object
     */
    public function listImportHistories(array $filter, User $user)
    {
        return (new ListImportHistories($filter, $user))->handle();
    }
}
