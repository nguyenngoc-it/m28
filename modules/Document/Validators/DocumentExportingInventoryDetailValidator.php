<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Modules\Document\Models\Document;

class DocumentExportingInventoryDetailValidator extends Validator
{
    /** @var Document */
    protected $documentExportingInventory;

    public function rules()
    {
        return [
            'id' => 'required',
        ];
    }

    /**
     * @return Document
     */
    public function getDocumentExportingInventory(): Document
    {
        return $this->documentExportingInventory;
    }

    protected function customValidate()
    {
        $documentId = $this->input('id');
        if (!$this->documentExportingInventory = Document::query()->where([
            'id' => $documentId,
            'tenant_id' => $this->user->tenant_id,
            'type' => Document::TYPE_EXPORTING_INVENTORY
        ])->first()) {
            $this->errors()->add('id', 'exists');
            return;
        }
    }
}
