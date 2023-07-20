<?php

namespace Modules\OrderIntegration\Validators;

use App\Base\Validator;
use Modules\Tenant\Models\Tenant;

class CreateShopeeDocumentValidator extends Validator
{
    /** @var Tenant $tenant */
    protected $tenant;
    /** @var array $orderCodes */
    protected $orderCodes = [];

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'tenant_code' => 'required',
            'order_codes' => 'required|array',
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
     * @return array
     */
    public function getOrderCodes(): array
    {
        return $this->orderCodes;
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
        $this->orderCodes = $this->input('order_codes', []);
    }
}
