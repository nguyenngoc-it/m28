<?php

namespace Modules\OrderIntegration\Validators;

use App\Base\Validator;
use Illuminate\Http\UploadedFile;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Tenant\Models\Tenant;

class ImportingExpectedTransportingPriceValidator extends Validator
{
    /** @var Tenant $tenant */
    protected $tenant;
    /** @var UploadedFile $file */
    protected $file;
    /** @var ShippingPartner $shippingPartner */
    protected $shippingPartner;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'tenant_code' => 'required',
            'file' => 'required',
            'shipping_partner_code' => 'required'
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
     * @return UploadedFile
     */
    public function getFile(): UploadedFile
    {
        return $this->file;
    }

    /**
     * @return ShippingPartner
     */
    public function getShippingPartner(): ShippingPartner
    {
        return $this->shippingPartner;
    }

    /**
     * Custom validate
     */
    protected function customValidate()
    {
        $tenantCode   = trim($this->input['tenant_code']);
        $this->tenant = Tenant::query()->firstWhere('code', $tenantCode);
        if (!$this->tenant instanceof Tenant) {
            $this->errors()->add('tenant', static::ERROR_EXISTS);
            return;
        }

        $this->file = $this->input('file');
        /**
         * File không đúng định dạng
         */
        if (!$this->file instanceof UploadedFile) {
            $this->errors()->add('file', static::ERROR_INVALID);
            return;
        }

        $shippingPartnerCode   = trim($this->input('shipping_partner_code'));
        $this->shippingPartner = ShippingPartner::query()->where([
            'tenant_id' => $this->tenant->id,
            'code' => $shippingPartnerCode
        ])->first();
        if (empty($this->shippingPartner)) {
            $this->errors()->add('shipping_partner_code', static::ERROR_EXISTS);
            return;
        }
    }

}
