<?php

namespace Modules\Service\Validators;

use App\Base\Validator;
use Gobiz\Log\LogService;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServicePrice;

class IsDefaultServicePriceValidator extends Validator
{
    /** @var boolean */
    protected $isDefault;
    /** @var ServicePrice */
    protected $servicePrice;

    public function __construct(Service $service, ServicePrice $servicePrice, array $input = [])
    {
        parent::__construct($input);
        $this->servicePrice = $servicePrice;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'is_default' => 'required|boolean',
        ];
    }

    /**
     * @return ServicePrice
     */
    public function getServicePrice(): ServicePrice
    {
        return $this->servicePrice;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    protected function customValidate()
    {
        $this->isDefault = $this->input('is_default');
        if (!$this->isDefault) {
            $this->errors()->add('is_default', static::ERROR_INVALID);
            return;
        }
        LogService::logger('test')->info($this->servicePrice->is_default . ' - ' . $this->isDefault);
    }
}
