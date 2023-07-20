<?php

namespace Modules\Service\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServicePack;
use Modules\Service\Models\ServicePrice;
use Modules\User\Models\User;

class UpdateServicePackValidator extends Validator
{
    /** @var Location */
    protected $country;
    /** @var ServicePack $servicePack */
    protected $servicePack;

    public function __construct(array $input, ServicePack $servicePack, User $user = null)
    {
        parent::__construct($input, $user);
        $this->servicePack = $servicePack;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
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
        $servicePriceIds = $this->input('service_price_ids', []);

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
