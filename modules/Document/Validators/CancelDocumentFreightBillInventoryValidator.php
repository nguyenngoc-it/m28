<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Modules\Document\Models\Document;

class CancelDocumentFreightBillInventoryValidator extends Validator
{
    /** @var Document */
    protected $document;

    public function rules()
    {
        return [
            'id' => 'required|int',
        ];
    }

    /**
     * @return Document
     */
    public function getDocument(): Document
    {
        return $this->document;
    }

    protected function customValidate()
    {
        $documentId = $this->input('id');
        if (!$this->document = Document::query()->where(['id' => $documentId, 'tenant_id' => $this->user->tenant_id])->first()) {
            $this->errors()->add('id', 'exists');
            return;
        }

        if ($this->document->type != Document::TYPE_FREIGHT_BILL_INVENTORY) {
            $this->errors()->add('type', static::ERROR_INVALID);
            return;
        }

        if ($this->document->status != Document::STATUS_DRAFT) {
            $this->errors()->add('status', static::ERROR_INVALID);
            return;
        }
    }
}
