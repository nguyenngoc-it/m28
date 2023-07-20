<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Modules\Product\Models\Product;
use Modules\Service\Models\Service;

class MerchantCreateProductValidator extends Validator
{
    /** @var Product */
    protected $merchantProduct;

    public function rules()
    {
        return [
            'name' => 'required|string',
            'code' => 'string',
            'files' => 'array|max:5',
            'files.*' => 'file|mimes:jpg,jpeg,png|max:5120',
            'services' => 'array',
            'weight' => 'numeric',
            'height' => 'numeric',
            'width' => 'numeric',
            'length' => 'numeric'
        ];
    }


    protected function customValidate()
    {
        $code = $this->input('code');
        if ($code && $this->user->merchant->products->where('code', $code)->first()) {
            $this->errors()->add('code', static::ERROR_ALREADY_EXIST);
            return;
        }

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
