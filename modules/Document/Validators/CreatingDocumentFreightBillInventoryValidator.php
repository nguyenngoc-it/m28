<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Modules\ShippingPartner\Models\ShippingPartner;

class CreatingDocumentFreightBillInventoryValidator extends Validator
{
    /** @var ShippingPartner */
    protected $shippingPartner;

    public function rules()
    {
        return [
            'shipping_partner_id' => 'required',
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ];
    }

    /**
     * @return ShippingPartner
     */
    public function getShippingPartner(): ShippingPartner
    {
        return $this->shippingPartner;
    }

    protected function customValidate()
    {
        $shippingPartnerId = $this->input('shipping_partner_id', 0);
        if (!$this->shippingPartner = ShippingPartner::query()->where([
            'id' => $shippingPartnerId,
            'tenant_id' => $this->user->tenant_id
        ])->first()) {
            $this->errors()->add('shipping_partner_id', static::ERROR_EXISTS);
            return;
        }

        $locationIds   = $this->shippingPartner->locations->pluck('id')->toArray();
        $userLocations = $this->user->locations->whereIn('id', $locationIds)->count();
        if(!$userLocations) {
            $this->errors()->add('location', static::ERROR_INVALID);
            return;
        }
    }
}
