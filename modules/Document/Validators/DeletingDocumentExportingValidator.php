<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Modules\Document\Models\Document;

class DeletingDocumentExportingValidator extends Validator
{
    /** @var Document */
    protected $documentExporting;

    public function rules()
    {
        return [
            'id' => 'required|int',
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
        $documentId = $this->input('id');
        if (!$this->documentExporting = Document::query()->where(['id' => $documentId, 'tenant_id' => $this->user->tenant_id])->first()) {
            $this->errors()->add('id', 'exists');
            return;
        }

        if ($this->documentExporting->type != Document::TYPE_EXPORTING) {
            $this->errors()->add('type', static::ERROR_INVALID);
            return;
        }

        if ($this->documentExporting->status != Document::STATUS_DRAFT) {
            $this->errors()->add('status', static::ERROR_INVALID);
            return;
        }
    }
}
