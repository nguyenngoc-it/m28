<?php

namespace Modules\PurchasingPackage\Validators;

use App\Base\Validator;
use Modules\Product\Models\Sku;
use Modules\PurchasingPackage\Models\PurchasingPackage;

class AddingItemPurchasingPackageValidator extends Validator
{
    /** @var PurchasingPackage $purchasingPackage */
    protected $purchasingPackage;

    public function __construct(PurchasingPackage $purchasingPackage, array $input = [])
    {
        $this->purchasingPackage = $purchasingPackage;
        parent::__construct($input);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'package_items' => 'required|array',
            'package_items.*.quantity' => 'required',
            'package_items.*.sku_id' => 'required',
        ];
    }

    protected function customValidate()
    {
        $packageItems = $this->input('package_items', []);
        foreach ($packageItems as $packageItem) {
            $skuId = $packageItem['sku_id'];
            if (!Sku::query()->where('id', $skuId)
                ->where('tenant_id', $this->user->tenant_id)
                ->where('merchant_id', $this->purchasingPackage->merchant_id)
                ->first()) {
                $this->errors()->add('sku_id', static::ERROR_EXISTS);
                return;
            }
        }
    }
}
