<?php

namespace Modules\Service\Validators;

use App\Base\Validator;
use Modules\Service\Models\ServiceCombo;
use Modules\Service\Models\ServicePack;

class CreateServiceComboValidator extends Validator
{
    /** @var ServicePack */
    protected $servicePack;

    /**
     * service_price_quotas => [
     *  {'service_price_id': 1, 'quota' => 100}
     * ]
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'service_pack_id' => 'required|int',
            'code' => 'required',
            'name' => 'required',
            'note' => '',
            'using_days' => 'required|int',
            'using_skus' => 'required|int',
            'suggest_price' => 'required|numeric',
            'service_price_quotas' => 'required|array'
        ];
    }

    /**
     * @return ServicePack
     */
    public function getServicePack(): ServicePack
    {
        return $this->servicePack;
    }

    protected function customValidate()
    {
        $servicePackId      = $this->input('service_pack_id', 0);
        $code               = $this->input('code');
        $servicePriceQuotas = $this->input('service_price_quotas');
        $servicePriceQuotas = collect($servicePriceQuotas)->pluck('quota', 'service_price_id')->all();

        if (!$this->servicePack = ServicePack::find($servicePackId)) {
            $this->errors()->add('service_pack_id', static::ERROR_EXISTS);
            return;
        }
        $checkDuplicateCode = ServiceCombo::query()->where([
            'code' => $code,
            'service_pack_id' => $servicePackId,
        ])->first();
        if ($checkDuplicateCode) {
            $this->errors()->add('code', static::ERROR_DUPLICATED);
            return;
        }

        /**
         * Phải truyền lên đủ đơn giá dịch vụ của gói dịch vụ
         */
        if (count($servicePriceQuotas) != $this->servicePack->servicePackPrices->count()
            || $this->servicePack->servicePackPrices->count() != $this->servicePack->servicePackPrices->whereIn('service_price_id', array_keys($servicePriceQuotas))->count()) {
            $this->errors()->add('service_price_quotas', static::ERROR_INVALID);
        }
    }
}
