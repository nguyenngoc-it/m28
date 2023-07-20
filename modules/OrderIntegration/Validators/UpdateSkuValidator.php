<?php

namespace Modules\OrderIntegration\Validators;

use App\Base\Validator;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Sku;
use Modules\Tenant\Models\Tenant;

class UpdateSkuValidator extends Validator
{
    /** @var Tenant $tenant */
    protected $tenant;

    /** @var Merchant $merchant */
    protected $merchant;

    /** @var Sku $sku */
    protected $sku;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'tenant_code' => 'required',
            'merchant_code' => 'required',
            'sku_code' => 'required',
            'name' => 'required',
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
     * @return Sku
     */
    public function getSku(): Sku
    {
        return $this->sku;
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

        $this->sku = $this->merchant->skus()->firstWhere('code', trim($this->input['sku_code']));
        if (!$this->sku instanceof Sku) {
            $this->errors()->add('sku_code', self::ERROR_INVALID);
            return;
        }
    }
}
