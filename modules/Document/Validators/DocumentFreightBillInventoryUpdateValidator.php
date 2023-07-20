<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Illuminate\Support\Arr;
use Modules\Document\Models\Document;

class DocumentFreightBillInventoryUpdateValidator extends Validator
{
    /** @var Document */
    protected $documentInventory;

    public function rules()
    {
        return [
            'id' => 'required',
            'other_fee' => 'numeric|gte:0',
        ];
    }

    /**
     * @return Document
     */
    public function getDocumentInventory(): Document
    {
        return $this->documentInventory;
    }

    protected function customValidate()
    {
        $documentId = $this->input('id');
        if (!$this->documentInventory = Document::query()->where(['id' => $documentId, 'tenant_id' => $this->user->tenant_id])->first()) {
            $this->errors()->add('id', 'exists');
            return;
        }

        if ($this->documentInventory->type != Document::TYPE_FREIGHT_BILL_INVENTORY) {
            $this->errors()->add('type', static::ERROR_INVALID);
            return;
        }

        if ($this->documentInventory->status != Document::STATUS_DRAFT) {
            $this->errors()->add('type', static::ERROR_INVALID);
            return;
        }
    }
}
