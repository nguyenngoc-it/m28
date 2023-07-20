<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Illuminate\Support\Arr;
use Modules\Document\Models\ImportingBarcode;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Sku;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;

class SkuImportingScanValidator extends Validator
{
    /**
     * @var Warehouse
     */
    protected $warehouse;

    /**
     * @var WarehouseArea
     */
    protected $warehouseArea;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var Sku[]
     */
    protected $skus;

    public function rules()
    {
        return [
            'warehouse_id' => 'required',
            'barcode' => 'required',
            'barcode_type' => 'required|in:' . implode(',', [ImportingBarcode::TYPE_SKU_CODE, ImportingBarcode::TYPE_SKU_REF, ImportingBarcode::TYPE_SKU_ID]),
            'merchant_id' => 'int'
        ];
    }

    protected function customValidate()
    {
        if (!$this->warehouse = $this->user->warehouses->firstWhere('id', $this->input['warehouse_id'])) {
            $this->errors()->add('warehouse_id', static::ERROR_INVALID);
            return;
        }

        if (!$this->warehouseArea = $this->warehouse->getDefaultArea()) {
            $this->errors()->add('warehouse_area', static::ERROR_EXISTS);
            return;
        }

        $merchant   = null;
        $merchantId = Arr::get($this->input, 'merchant_id', 0);
        if (!empty($merchantId)) {
            $merchant = $this->user->tenant->merchants()->firstWhere('id', $merchantId);
            if (!$merchant instanceof Merchant) {
                $this->errors()->add('merchant_id', static::ERROR_EXISTS);
                return;
            }

        }

        $barcode = (array)$this->input['barcode'];
        $barcode = array_unique($barcode);

        $type          = $this->input['barcode_type'];
        $skuIds        = [];
        $barcodeExists = [];

        $merchantIds = $this->user->merchants()->where([
            'status' => true,
            'tenant_id' => $this->user->tenant_id
        ])->pluck('merchants.id')->toArray();

        foreach ($barcode as $code) {
            $code     = trim($code);
            $skuQuery = Sku::query()->select(['skus.*'])->where('skus.tenant_id', $this->user->tenant->id)
                ->join('products', 'skus.product_id', '=', 'products.id')
                ->join('product_merchants', 'products.id', '=', 'product_merchants.product_id')
                ->whereIn('product_merchants.merchant_id', $merchantIds);

            if ($type == ImportingBarcode::TYPE_SKU_CODE) {
                $skuQuery->where('skus.code', $code);
            } elseif ($type == ImportingBarcode::TYPE_SKU_REF) {
                $skuQuery->where('skus.ref', $code);
            } else {
                $skuQuery->where('skus.id', $code);
            }

            if ($merchant instanceof Merchant) {
                $skuQuery->where('product_merchants.merchant_id', $merchant->id);
            }

            $skuQuery->groupBy('skus.id');

            $skus = $skuQuery->get();
            if ($skus->count() > 1) {
                $this->errors()->add('has_many_in_merchant', $code);
                return;
            }
            $sku = $skuQuery->first();

            if (!$sku instanceof Sku) {
                $barcodeExists[] = $code;
                continue;
            }
            $skuIds[]     = $sku->id;
            $this->skus[] = $sku;
        }

        if (!empty($barcodeExists)) {
            $this->errors()->add('sku_exists', $barcodeExists);
            return;
        }
    }


    /**
     * @return Sku[]
     */
    public function getSkus()
    {
        return $this->skus;
    }
}
