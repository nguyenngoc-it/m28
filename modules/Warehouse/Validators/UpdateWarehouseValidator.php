<?php

namespace Modules\Warehouse\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;
use Modules\Warehouse\Models\Warehouse;

class UpdateWarehouseValidator extends Validator
{
    /**
     * UpdateWarehouseValidator constructor.
     * @param Warehouse $warehouse
     * @param array $input
     */
    public function __construct(Warehouse $warehouse, array $input)
    {
        $this->warehouse = $warehouse;
        parent::__construct($input);
    }


    /**
     * @var Warehouse
     */
    protected $warehouse;


    /**
     * @var Location|null
     */
    protected $receiverCountry;

    /**
     * @var Location|null
     */
    protected $receiverProvince;

    /**
     * @var Location|null
     */
    protected $receiverDistrict;

    /**
     * @var Location|null
     */
    protected $receiverWard;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'code' => 'required',
            'name' => 'required',
            'country_id' => 'required'
        ];
    }

    protected function customValidate()
    {
        $code = trim($this->input['code']);
        $tenant = $this->warehouse->tenant;
        if (
            $this->warehouse->code != $code &&
            $tenant->warehouses()->firstWhere('code', $code)
        ) {
            $this->errors()->add('code', static::ERROR_ALREADY_EXIST);
        }

        if(!empty($this->input['country_id'])) {
            if(!$this->receiverCountry = Location::query()->firstWhere([
                'id' => $this->input['country_id'],
                'type' => Location::TYPE_COUNTRY
            ])) {
                $this->errors()->add('country_id', static::ERROR_NOT_EXIST);
            }
        }

        if(!empty($this->input['province_id'])) {
            if(!$this->receiverProvince = Location::query()->firstWhere([
                'id' => $this->input['province_id'],
                'type' => Location::TYPE_PROVINCE
            ])) {
                $this->errors()->add('province_id', static::ERROR_NOT_EXIST);
            }
        }

        if(!empty($this->input['district_id'])) {
            if(!$this->receiverDistrict = Location::query()->firstWhere([
                'id' => $this->input['district_id'],
                'type' => Location::TYPE_DISTRICT
            ])) {
                $this->errors()->add('district_id', static::ERROR_NOT_EXIST);
            }
        }

        if(!empty($this->input['ward_id'])) {
            if(!$this->receiverWard = Location::query()->firstWhere([
                'id' => $this->input['ward_id'],
                'type' => Location::TYPE_WARD
            ])) {
                $this->errors()->add('ward_id', static::ERROR_NOT_EXIST);
            }
        }
    }

}
