<?php

namespace Modules\Document\Services;

use Gobiz\ModelQuery\ModelQuery;
use Modules\Document\Commands\CreateDocument;
use Modules\Document\Models\Document;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;

class DocumentService implements DocumentServiceInterface
{
    /**
     * Create document
     *
     * @param array $input
     * @param User $creator
     * @param Warehouse|null $warehouse
     * @return Document
     */
    public function create(array $input, User $creator, Warehouse $warehouse = null)
    {
        return (new CreateDocument($input, $creator, $warehouse))->handle();
    }

    /**
     * Cập nhật thông tin chứng từ
     *
     * @param Document $document
     * @param array $inputs
     * @param User $user
     * @return Document
     */
    public function update(Document $document, array $inputs, User $user)
    {
        $updatedPayload = [];
        foreach ($inputs as $key => $input) {
            if ($document->{$key} != $input) {
                $updatedPayload[$key]['old'] = $document->{$key};
                $updatedPayload[$key]['new'] = $input;
                $document->{$key}            = $input;
            }
        }
        if ($updatedPayload) {

            $document->save();
            $document->logActivity(DocumentEvent::UPDATE, $user, [
                'document' => $document,
                'updated' => $updatedPayload
            ]);
        }
        return $document;
    }

    /**
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter)
    {
        return (new DocumentQuery())->query($filter);
    }
}
