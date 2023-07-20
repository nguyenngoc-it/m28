<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServicePrice;

class UpdateMerchantProductValidator extends Validator
{

    /**
     * @return array|string[]
     */
    public function rules()
    {
        return [
            'services' => 'array',
        ];
    }


    protected function customValidate()
    {
        $serviceIds = $this->input('services');
        /**
         * Nếu seller đang dùng gói dịch vụ thì bắt buộc các dịch vụ phải nằm trong gói
         * và phải đầy đủ 4 gói dv
         */
        if ($serviceIds && $this->user->merchant->servicePack) {
            $packServiceIds = $this->user->merchant->servicePack->servicePackPrices->pluck('service_id')->all();
            if (array_diff($serviceIds, $packServiceIds)) {
                $this->errors()->add('services', 'not_in_service_pack');
                return;
            }
            $serviceGroups = Service::query()->whereIn('id', $serviceIds);
            if (array_diff(Service::$groupForPacks, $serviceGroups->pluck('type')->unique()->all())) {
                $this->errors()->add('service_groups', 'missing');
            }
        }
    }
}
