<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentSkuInventory;

class UpdatingDocumentSkuInventoryValidator extends Validator
{
    /** @var DocumentSkuInventory|null */
    protected $skuInventory;
    /** @var Document */
    protected $documentSkuInventory;

    public function rules()
    {
        return [
            'id' => 'required',
            'sku_id' => 'required_with:quantity|int',
            'warehouse_area_id' => 'required_with:sku_id|int',
            'explain' => 'string',
            'note'
        ];
    }

    /**
     * @return Document
     */
    public function getDocumentSkuInventory(): Document
    {
        return $this->documentSkuInventory;
    }

    /**
     * @return DocumentSkuInventory|null
     */
    public function getSkuInventory(): ?DocumentSkuInventory
    {
        return $this->skuInventory;
    }

    protected function customValidate()
    {
        $documentId      = $this->input('id');
        $skuId           = $this->input('sku_id', 0);
        $warehouseAreaId = $this->input('warehouse_area_id', 0);
        if (!$this->documentSkuInventory = Document::query()->where(['id' => $documentId, 'tenant_id' => $this->user->tenant_id])->first()) {
            $this->errors()->add('id', 'exists');
            return;
        }
        if ($this->documentSkuInventory->type != Document::TYPE_SKU_INVENTORY) {
            $this->errors()->add('type', static::ERROR_INVALID);
            return;
        }

        if ($skuId && $warehouseAreaId && !$this->skuInventory = DocumentSkuInventory::query()->where([
                'document_id' => $documentId,
                'sku_id' => $skuId,
                'warehouse_area_id' => $warehouseAreaId
            ])->first()) {
            $this->errors()->add('sku_inventory', static::ERROR_EXISTS);
            return;
        }

    }
}
