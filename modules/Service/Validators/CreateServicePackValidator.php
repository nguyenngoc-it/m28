<?php

namespace Modules\Service\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServicePack;
use Modules\Service\Models\ServicePrice;

class CreateServicePackValidator extends Validator
{
    /** @var Location */
    protected $country;

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'country_id' => 'required|int',
            'code' => 'required',
            'name' => 'required',
            'note' => '',
            'service_price_ids' => 'required|array',
        ];
    }

    /**
     * @return Location
     */
    public function getCountry(): Location
    {
        return $this->country;
    }

    protected function customValidate()
    {
        $countryId       = $this->input('country_id');
        $code            = $this->input('code');
        $servicePriceIds = $this->input('service_price_ids', []);
        if (!($this->country = Location::query()->where('id', $countryId)->where('active', true)
            ->where('type', Location::TYPE_COUNTRY)
            ->first())) {
            $this->errors()->add('country_id', static::ERROR_EXISTS);
            return;
        }
        $checkDuplicateCode = ServicePack::query()->where([
            'code' => $code,
            'tenant_id' => $this->user->tenant->id,
        ])->first();
        if ($checkDuplicateCode) {
            $this->errors()->add('code', static::ERROR_DUPLICATED);
            return;
        }

        /**
         * Kiểm tra có đủ các nhóm dịch vụ để tạo thành gói dv hay ko
         */
        $serviceGroups = ServicePrice::query()->whereIn('id', $servicePriceIds)->chunkMap(function (ServicePrice $servicePrice) {
            return [
                'service_id' => $servicePrice->service->id,
                'service_type' => $servicePrice->service->type
            ];
        }, 10)->values();
        $serviceIds    = $serviceGroups->pluck('service_id')->all();
        if (count($serviceIds) != count(array_unique($serviceIds))) {
            $this->errors()->add('services', static::ERROR_DUPLICATED);
            return;
        }
        if (array_diff(Service::$groupForPacks, $serviceGroups->pluck('service_type')->all())) {
            $this->errors()->add('service_groups', 'missing');
        }
    }
}
