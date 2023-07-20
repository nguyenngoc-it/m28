<?php

namespace Modules\PurchasingOrder\Validators;

use App\Base\Validator;
use Modules\Product\Models\Sku;
use Modules\PurchasingOrder\Models\PurchasingOrder;
use Modules\PurchasingOrder\Models\PurchasingVariant;

class PurchasingOrderMappingVariantValidator extends Validator
{
    /** @var PurchasingOrder */
    protected $purchasingOrder;
    /** @var PurchasingVariant */
    protected $purchasingVariant;
    /** @var Sku */
    protected $sku;

    public function rules()
    {
        return [
            'id' => 'required',
            'purchasing_variant_id' => 'required',
            'sku_id' => 'required',
        ];
    }

    /**
     * @return PurchasingVariant
     */
    public function getPurchasingVariant(): PurchasingVariant
    {
        return $this->purchasingVariant;
    }

    /**
     * @return PurchasingOrder
     */
    public function getPurchasingOrder(): PurchasingOrder
    {
        return $this->purchasingOrder;
    }

    /**
     * @return Sku
     */
    public function getSku(): Sku
    {
        return $this->sku;
    }

    protected function customValidate()
    {
        $purchasingOrderId   = $this->input('id');
        $purchasingVariantId = $this->input('purchasing_variant_id');
        $skuId               = $this->input('sku_id');
        if (!$this->purchasingOrder = PurchasingOrder::query()->where(['id' => $purchasingOrderId, 'tenant_id' => $this->user->tenant_id])->first()) {
            $this->errors()->add('purchasing_order', 'exists');
            return;
        }
        if (!$this->purchasingVariant = PurchasingVariant::query()->where(['id' => $purchasingVariantId, 'tenant_id' => $this->user->tenant_id])->first()) {
            $this->errors()->add('purchasing_variant', 'exists');
            return;
        }
        if (!$this->sku = Sku::query()->where(['id' => $skuId, 'tenant_id' => $this->user->tenant_id])->first()) {
            $this->errors()->add('sku', 'exists');
            return;
        }
    }
}
