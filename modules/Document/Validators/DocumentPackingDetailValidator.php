<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Modules\Document\Models\Document;

class DocumentPackingDetailValidator extends Validator
{
    /** @var Document */
    protected $documentPacking;

    public function rules()
    {
        return [
            'id' => 'required',
        ];
    }

    /**
     * @return Document
     */
    public function getDocumentPacking(): Document
    {
        return $this->documentPacking;
    }

    protected function customValidate()
    {
        $documentId = $this->input('id');
        if (!$this->documentPacking = Document::query()->where(['id' => $documentId, 'tenant_id' => $this->user->tenant_id])->first()) {
            $this->errors()->add('id', 'exists');
            return;
        }

        if ($this->documentPacking->type != Document::TYPE_PACKING) {
            $this->errors()->add('type', static::ERROR_INVALID);
            return;
        }
    }
}
