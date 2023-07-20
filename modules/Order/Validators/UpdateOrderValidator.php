<?php

namespace Modules\Order\Validators;

use App\Base\Validator;
use Modules\Location\Models\Location;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Models\Order;

class UpdateOrderValidator extends Validator
{
    /**
     * UpdateOrderValidator constructor.
     * @param Order $order
     * @param array $input
     */
    public function __construct(Order $order, array $input)
    {
        $this->order = $order;
        parent::__construct($input);
    }

    /**
     * @var Order
     */
    private $order;

    /**
     * @var Merchant
     */
    private $merchant;


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
            'description' => 'string',
            'receiver_address' => 'string',
            'receiver_postal_code' => 'string'
        ];
    }

    protected function customValidate()
    {

        if (!$this->order->canUpdateOrder() ) {
            if (count($this->order->freightBills) == 0){
                $receiverPostalCode = data_get($this->input, 'receiver_postal_code');
                $this->input = [];
                $this->input['receiver_postal_code'] = $receiverPostalCode;
                return;
            }
            else{
                $this->errors()->add('order_status', self::ERROR_INVALID);
                return;
            }
        }

        if (count($this->order->freightBills) > 0) {
            $this->errors()->add('order_freightBill', self::ERROR_INVALID);
        }

        if (
            !empty($this->input['receiver_province_id']) ||
            !empty($this->input['receiver_district_id']) ||
            !empty($this->input['receiver_ward_id'])
        ) {
            $this->merchant         = $this->order->merchant;
            $this->receiverProvince = $this->order->receiverProvince;
            $this->receiverDistrict = $this->order->receiverProvince;
            $this->receiverWard     = $this->order->receiverProvince;

            $locationErrors = $this->validateLocation();
            if (!empty($locationErrors)) {
                $this->errors()->add('location_errors', $locationErrors);
            }
        }
    }

    /**
     * @return array
     */
    public function getInputs(){
        return $this->input;
    }

    /**
     * @return array
     */
    protected function validateLocation()
    {
        $locationErrors = [];

        if (!empty($this->input['receiver_province_id'])) {
            if (!$this->receiverProvince = Location::query()->firstWhere([
                'id' => $this->input['receiver_province_id'],
                'type' => Location::TYPE_PROVINCE
            ])) {
                $locationErrors['receiver_province_id'] = static::ERROR_NOT_EXIST;
            }
        }

        $country     = $this->merchant->getCountry();
        $countryCode = ($country instanceof Location) ? $country->code : '';
        if ($this->receiverProvince && $this->receiverProvince->parent_code != trim($countryCode)) {
            $locationErrors['receiver_province_id'] = static::ERROR_INVALID;
        }

        if (!empty($this->input['receiver_district_id'])) {
            if (!$this->receiverDistrict = Location::query()->firstWhere([
                'id' => $this->input['receiver_district_id'],
                'type' => Location::TYPE_DISTRICT
            ])) {
                $locationErrors['receiver_district_id'] = static::ERROR_NOT_EXIST;
            }
        }

        if (
            $this->receiverProvince instanceof Location &&
            $this->receiverDistrict &&
            $this->receiverDistrict->parent_code != $this->receiverProvince->code
        ) {
            $locationErrors['receiver_district_id'] = static::ERROR_INVALID;
        }

        if (!empty($this->input['receiver_ward_id'])) {
            if (!$this->receiverWard = Location::query()->firstWhere([
                'id' => $this->input['receiver_ward_id'],
                'type' => Location::TYPE_WARD
            ])) {
                $locationErrors['receiver_ward_id'] = static::ERROR_NOT_EXIST;
            }
        }

        if (
            !$this->receiverDistrict instanceof Location ||
            (
                $this->receiverWard && $this->receiverWard->parent_code != $this->receiverDistrict->code
            )
        ) {
            $locationErrors['receiver_ward_id'] = static::ERROR_INVALID;
        }

        return $locationErrors;
    }
}
