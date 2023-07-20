<?php

namespace Modules\Service\Validators;

use App\Base\Validator;
use Modules\Service\Models\Service;

class ChangeServiceStatusValidator extends Validator
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
            'status' => 'required|in:'. Service::STATUS_ACTIVE . ',' . Service::STATUS_INACTIVE,
            'confirm' => 'required|boolean',
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
