<?php

namespace Modules\PurchasingPackage\Validators;

use App\Base\Validator;
use Illuminate\Database\Eloquent\Collection;
use Modules\PurchasingPackage\Models\PurchasingPackage;

class ToTransportingMerchantPurchasingPackageValidator extends Validator
{
    /** @var Collection */
    protected $purchasingPackages;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'purchasing_package_ids' => 'required|array',
        ];
    }

    /**
     * @return Collection
     */
    public function getPurchasingPackages(): Collection
    {
        return $this->purchasingPackages;
    }

    protected function customValidate()
    {
        $purchasingPackageIds     = $this->input('purchasing_package_ids', []);
        $this->purchasingPackages = PurchasingPackage::query()->whereIn('id', $purchasingPackageIds)
            ->where('merchant_id', $this->user->merchant->id)->get();
        if ($this->purchasingPackages->count() < count($purchasingPackageIds)) {
            $this->errors()->add('purchasing_package_ids', static::ERROR_INVALID);
            return;
        }
    }
}
