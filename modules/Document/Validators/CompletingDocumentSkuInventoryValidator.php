<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentSkuInventory;

class CompletingDocumentSkuInventoryValidator extends Validator
{
    /** @var Document */
    protected $documentInventory;

    public function rules()
    {
        return [
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

    protected function customValidate()
    {
        $documentId = $this->input('id');
        if (!$this->documentInventory = Document::query()->where(['id' => $documentId, 'tenant_id' => $this->user->tenant_id])->first()) {
            $this->errors()->add('id', 'exists');
            return;
        }
        if ($this->documentInventory->type != Document::TYPE_SKU_INVENTORY) {
            $this->errors()->add('type', static::ERROR_INVALID);
            return;
        }
        if ($this->documentInventory->status != Document::STATUS_DRAFT) {
            $this->errors()->add('id', static::ERROR_STATUS_INVALID);
            return;
        }

        /**
         * Nếu còn sku chưa kiểm hoặc cân bằng khác 0 thì không được kết thúc kiểm kê
         */
        /** @var DocumentSkuInventory $skuInventory */
        foreach ($this->documentInventory->documentSkuInventories as $skuInventory) {
            if (!empty($skuInventory->quantity_balanced)) {
                $this->errors()->add('sku_inventory', [
                    'message' => 'require_balanced',
                    'code' => $skuInventory->sku->code
                ]);
                return;
            }
        }

    }
}
