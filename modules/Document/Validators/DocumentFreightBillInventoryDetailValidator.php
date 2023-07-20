<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Modules\Document\Models\Document;

class DocumentFreightBillInventoryDetailValidator extends Validator
{
    /** @var Document */
    protected $documentInventory;

    public function rules()
    {
        return [
            'id' => 'required',
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
    }
}
