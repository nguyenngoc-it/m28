<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Modules\Document\Models\Document;

class UpdatingDocumentImportingValidator extends Validator
{
    /** @var Document */
    protected $documentImporting;

    public function rules()
    {
        return [
            'id' => 'required',
            'receiver_name' => 'string',
            'receiver_phone' => 'string',
            'receiver_license' => 'string',
            'partner' => 'string',
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

        if ($this->documentImporting->type != Document::TYPE_IMPORTING) {
            $this->errors()->add('type', static::ERROR_INVALID);
            return;
        }

        if ($this->documentImporting->status != Document::STATUS_DRAFT) {
            $this->errors()->add('status', static::ERROR_INVALID);
            return;
        }
    }
}
