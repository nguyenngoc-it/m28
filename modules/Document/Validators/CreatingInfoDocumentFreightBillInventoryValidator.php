<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Modules\Document\Models\Document;

class CreatingInfoDocumentFreightBillInventoryValidator extends Validator
{
    /** @var Document */
    protected $documentInventory;

    /**
     * @return string[]
     */
    public function rules()
    {
        return [
            'received_date' => 'date_format:d/m/Y H:i',
            'payment_slip' => 'string',
            'received_pay_date' => 'date_format:d/m/Y H:i',
            'id' => 'required'
        ];
    }

    /**
     * @return Document
     */
    public function getDocumentInventory(): Document
    {
        return $this->documentInventory;
    }

    public function customValidate()
    {
        $documentId = $this->input('id');
        if (!$this->documentInventory = Document::query()->where(['id' => $documentId, 'tenant_id' => $this->user->tenant_id])->first()) {
            $this->errors()->add('id', 'exists');
            return;
        }
        $receivedDate = $this->input('received_date');
        if ($this->documentInventory->created_at < $receivedDate) {
            $this->errors()->add('received_date', static::ERROR_INVALID);
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
