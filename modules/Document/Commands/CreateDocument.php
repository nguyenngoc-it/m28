<?php

namespace Modules\Document\Commands;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Document\Models\Document;
use Modules\Document\Services\DocumentEvent;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;

class CreateDocument
{
    /**
     * @var Warehouse|null
     */
    protected $warehouse;

    /**
     * @var array
     */
    protected $input;

    /**
     * @var User
     */
    protected $creator;

    /**
     * CreateDocument constructor
     *
     * @param Warehouse $warehouse
     * @param array $input
     * @param User $creator
     */
    public function __construct(array $input, User $creator, Warehouse $warehouse = null)
    {
        $this->warehouse = $warehouse;
        $this->input     = $input;
        $this->creator   = $creator;
    }

    /**
     * @return Document
     */
    public function handle()
    {
        $document = $this->createDocument();

        $document->logActivity(DocumentEvent::CREATE, $this->creator, [
            'document' => $document,
        ]);

        return $document;
    }

    /**
     * @return Document
     */
    protected function createDocument()
    {
        $input = $this->input;

        if (!$codePrefix = Document::getCodePrefix($input['type'])) {
            throw new InvalidArgumentException("Can't find code prefix for document type {$input['type']}");
        }

        $input = array_merge($input, [
            'tenant_id' => $this->creator->tenant_id,
            'warehouse_id' => $this->warehouse ? $this->warehouse->id : 0,
            'creator_id' => $this->creator->id,
        ]);

        return DB::transaction(function () use ($input, $codePrefix) {
            $document = Document::create($input);
            $document->update(['code' => $codePrefix . $document->id]);

            return $document;
        });
    }
}
