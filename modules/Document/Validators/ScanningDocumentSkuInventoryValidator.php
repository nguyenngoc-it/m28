<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Illuminate\Support\Arr;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentSkuInventory;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Sku;
use Modules\User\Models\User;
use Modules\Warehouse\Models\WarehouseArea;

class ScanningDocumentSkuInventoryValidator extends Validator
{
    /** @var Document */
    protected $documentSkuInventory;
    /** @var Sku|null */
    protected $sku;
    /** @var int */
    protected $quantity;
    /**
     * @var User
     */
    protected $user;
    /**
     * @var WarehouseArea
     */
    protected $warehouseArea;

    public function rules()
    {
        return [
            'id' => 'required|int',
            'barcode' => 'required|string',
            'barcode_type' => 'required|in:' . DocumentSkuInventory::TYPE_SKU_CODE . ',' . DocumentSkuInventory::TYPE_SKU_REF . ',' . DocumentSkuInventory::TYPE_SKU_ID,
            'quantity' => 'int',
            'merchant_id' => 'int',
            'warehouse_area_id' => 'required|int',
        ];
    }

    /**
     * @return Sku|null
     */
    public function getSku(): ?Sku
    {
        return $this->sku;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @return Document
     */
    public function getDocumentSkuInventory(): Document
    {
        return $this->documentSkuInventory;
    }

    /**
     * @return WarehouseArea
     */
    public function getWarehouseArea(): WarehouseArea
    {
        return $this->warehouseArea;
    }

    protected function customValidate()
    {
        $documentId     = $this->input('id', 0);
        $barcode        = trim($this->input('barcode'));
        $barcodeType    = $this->input('barcode_type');
        $this->quantity = $this->input('quantity', 1);


        if (!$this->documentSkuInventory = Document::query()->where([
            'tenant_id' => $this->user->tenant_id,
            'id' => $documentId
        ])->first()) {
            $this->errors()->add('id', static::ERROR_EXISTS);
            return;
        }
        if ($this->documentSkuInventory->status != Document::STATUS_DRAFT) {
            $this->errors()->add('id', static::ERROR_STATUS_INVALID);
            return;
        }

        $merchantIds = $this->user->merchants()->where([
            'status' => true,
            'tenant_id' => $this->user->tenant_id
        ])->pluck('merchants.id')->toArray();

        $skuQuery = Sku::query()->select(['skus.*'])->where('skus.tenant_id', $this->user->tenant_id)
            ->join('products', 'skus.product_id', '=', 'products.id')
            ->join('product_merchants', 'products.id', '=', 'product_merchants.product_id')
            ->whereIn('product_merchants.merchant_id', $merchantIds);

        if ($barcodeType == DocumentSkuInventory::TYPE_SKU_CODE) {
            $skuQuery->where('skus.code', $barcode);
        }
        if ($barcodeType == DocumentSkuInventory::TYPE_SKU_REF) {
            $skuQuery->where('skus.ref', $barcode);
        }
        if ($barcodeType == DocumentSkuInventory::TYPE_SKU_ID) {
            $skuQuery->where('skus.id', $barcode);
        }

        $merchant   = null;
        $merchantId = Arr::get($this->input, 'merchant_id', 0);
        if (!empty($merchantId)) {
            $merchant = $this->documentSkuInventory->tenant->merchants()->firstWhere('id', $merchantId);
            if (!$merchant instanceof Merchant) {
                $this->errors()->add('merchant_id', static::ERROR_EXISTS);
                return;
            }
            $skuQuery->where('product_merchants.merchant_id', $merchant->id);
        }
        $skuQuery->groupBy('skus.id');
        $skus = $skuQuery->get();
        if ($skus->count() > 1) {
            $this->errors()->add('has_many_in_merchant', $barcode);
            return;
        }

        $this->warehouseArea = $this->documentSkuInventory->warehouse->areas()->where('id', $this->input['warehouse_area_id'])->first();
        if (!$this->warehouseArea instanceof WarehouseArea) {
            $this->errors()->add('warehouse_area_id', static::ERROR_STATUS_INVALID);
            return;
        }

        if (!$this->sku = $skus->first()) {
            $this->errors()->add('sku', static::ERROR_EXISTS);
            return;
        }
    }
}
