<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Modules\Document\Models\Document;

class CreatingDocumentExportingInventoryValidator extends Validator
{
    /** @var Document */
    protected $documentExporting;

    public function rules()
    {
        return [
            'document_id' => 'required|int',
            'barcodes' => 'required|array',
            'uncheck_barcodes' => 'array',
        ];
    }

    /**
     * @return Document
     */
    public function getDocumentExporting(): Document
    {
        return $this->documentExporting;
    }

    protected function customValidate()
    {
        $documentId = $this->input('document_id');
        if (!$this->documentExporting = Document::query()->where(
            [
                'id' => $documentId,
                'tenant_id' => $this->user->tenant_id,
                'type' => Document::TYPE_EXPORTING
            ])->first()) {
            $this->errors()->add('document_id', 'exists');
            return;
        }

        if ($this->documentExporting->status != Document::STATUS_COMPLETED) {
            $this->errors()->add('status', static::ERROR_INVALID);
            return;
        }
    }
}
