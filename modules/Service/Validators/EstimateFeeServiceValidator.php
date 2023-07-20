<?php

namespace Modules\Service\Validators;

use App\Base\Validator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Location\Models\Location;
use Modules\Service\Models\Service;

class EstimateFeeServiceValidator extends Validator
{
    /** @var Collection */
    protected $services;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'height' => 'required|numeric',
            'width' => 'required|numeric',
            'length' => 'required|numeric',
            'quantity' => 'required|int',
            'service_ids' => 'array',
            'seller_ref' => ''
        ];
    }

    /**
     * @return Collection|null
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    protected function customValidate()
    {
        /** @var Location $country */
        $country        = Location::query()->where('code', 'vietnam')->first();
        $this->services = Service::query()->where('country_id', $country->id)->get();
        $serviceIds     = $this->input('service_ids', []);
        if ($serviceIds) {
            if ($this->services->count() < count($serviceIds)) {
                $this->errors()->add('service_ids', 'not_in_country');
                return;
            }
        }
        $serviceRequiredIds = $this->services->where('is_required', true)->pluck('id')->all();
        $serviceIds         = array_unique(array_merge($serviceIds, $serviceRequiredIds));
        $this->services     = $this->services->whereIn('id', $serviceIds);
    }
}
