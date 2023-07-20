<?php

namespace Modules\OrderIntegration\Validators;

use App\Base\Validator;
use Modules\Merchant\Models\Merchant;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\Tenant\Models\Tenant;

class CancelPurchasePackageValidator extends Validator
{
    /** @var Tenant $tenant */
    protected $tenant;

    /** @var Merchant $merchant */
    protected $merchant;

    /** @var PurchasingPackage $purchasingPackage */
    protected $purchasingPackage;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'tenant_code' => 'required',
            'merchant_code' => 'required',
            'package_code' => 'required'
        ];
    }

    /**
     * @return Tenant
     */
    public function getTenant(): Tenant
    {
        return $this->tenant;
    }

    /**
     * @return Merchant
     */
    public function getMerchant(): Merchant
    {
        return $this->merchant;
    }

    /**
     * @return PurchasingPackage
     */
    public function getPurchasingPackage(): PurchasingPackage
    {
        return $this->purchasingPackage;
    }

    /**
     * Custom validate
     */
    protected function customValidate()
    {
        $tenantCode   = trim($this->input['tenant_code']);
        $this->tenant = Tenant::query()->firstWhere('code', $tenantCode);
        if (!$this->tenant instanceof Tenant) {
            $this->errors()->add('tenant', self::ERROR_INVALID);
            return;
        }
        $this->merchant = $this->tenant->merchants()->firstWhere('code', trim($this->input['merchant_code']));
        if (!$this->merchant instanceof Merchant) {
            $this->errors()->add('merchant_code', self::ERROR_INVALID);
            return;
        }

        $this->purchasingPackage = $this->merchant->purchasingPackages()->firstWhere('code', trim($this->input['package_code']));
        if (!$this->purchasingPackage instanceof PurchasingPackage) {
            $this->errors()->add('package_code', self::ERROR_INVALID);
            return;
        }

        if($this->purchasingPackage->status != PurchasingPackage::STATUS_INIT) {
            $this->errors()->add('status', self::ERROR_INVALID);
            return;
        }
    }
}
