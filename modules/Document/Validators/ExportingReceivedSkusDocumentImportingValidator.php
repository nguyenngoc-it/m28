<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Modules\Document\Models\Document;

class ExportingReceivedSkusDocumentImportingValidator extends Validator
{
    /** @var Document */
    protected $documentImporting;

    public function rules()
    {
        return [
            'id' => 'required',
        ];
    }

    /**
     * @return Document
     */
    public function getDocumentImporting(): Document
    {
        return $this->documentImporting;
    }

    protected function customValidate()
    {
        $documentId = $this->input('id');
        if (!$this->documentImporting = Document::query()->where(['id' => $documentId, 'tenant_id' => $this->user->tenant_id])->first()) {
            $this->errors()->add('id', 'exists');
            return;
        }

        if (!in_array($this->documentImporting->type, [Document::TYPE_IMPORTING, Document::TYPE_IMPORTING_RETURN_GOODS])) {
            $this->errors()->add('type', static::ERROR_INVALID);
            return;
        }
    }
}
