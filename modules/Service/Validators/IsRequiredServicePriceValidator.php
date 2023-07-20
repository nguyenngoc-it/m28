<?php

namespace Modules\Service\Validators;

use App\Base\Validator;
use Modules\Service\Models\Service;

class IsRequiredServicePriceValidator extends Validator
{
    /** @var Service */
    protected $service;


    public function __construct(Service $service, array $input = [])
    {
        parent::__construct($input);
        $this->service = $service;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'is_required' => 'required|boolean',
        ];
    }

    /**
     * @return Service
     */
    public function getService(): Service
    {
        return $this->service;
    }
}
