<?php

namespace Modules\Document\Services;

use Gobiz\ModelQuery\ModelQuery;
use Modules\Document\Models\Document;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;

interface DocumentServiceInterface
{
    /**
     * Create document
     *
     * @param Warehouse $warehouse
     * @param array $input
     * [
     *      'type', 'status', 'note', 'info', 'verifier_id', 'verified_at'
     * ]
     * @param User $creator
     * @return Document
     */
    public function create(array $input, User $creator, Warehouse $warehouse = null);

    /**
     * Cập nhật thông tin chứng từ
     *
     * @param Document $document
     * @param array $inputs
     * @param User $user
     * @return Document
     */
    public function update(Document $document, array $inputs, User $user);

    /**
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter);
}
