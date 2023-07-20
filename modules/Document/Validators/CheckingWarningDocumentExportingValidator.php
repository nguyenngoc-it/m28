<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Modules\Document\Models\Document;
use Modules\Order\Models\Order;
use PhpParser\Comment\Doc;

class CheckingWarningDocumentExportingValidator extends Validator
{
    /**
     * @var Document
     */
    protected $document;

    public function rules()
    {
        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'document_packing' => 'string',
            'receiver_name' => 'string',
            'receiver_phone' => 'string',
            'receiver_license' => 'string',
            'partner' => 'string',
        ];
    }

    protected function customValidate()
    {

        if ($documentPackingCode = $this->input('document_packing')) {
            $tenantId = $this->input('tenant_id', 0);
            $this->document = Document::query()->where([
                'tenant_id' => $tenantId,
                'code' => $documentPackingCode
            ])->first();
            if (!$this->document instanceof Document) {
                $this->errors()->add('document_packing', static::ERROR_EXISTS);
                return;
            }
        }

    }

    public function getDocument()
    {
        return $this->document;
    }
}
