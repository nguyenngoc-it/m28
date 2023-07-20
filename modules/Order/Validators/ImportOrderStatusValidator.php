<?php

namespace Modules\Order\Validators;

use App\Base\Validator;
use Modules\Category\Models\Category;
use Modules\Order\Models\Order;
use Modules\Product\Models\Unit;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;

class ImportOrderStatusValidator extends Validator
{
    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var array
     */
    protected $insertedOrderKeys = [];

    /**
     * @var array
     */
    protected $merchantIds = [];

    /**
     * ImportOrderStatusValidator constructor.
     * @param User $creator
     * @param array $input
     * @param array $insertedOrderKeys
     * @param $merchantIds
     */
    public function __construct(User $creator, array $input, $insertedOrderKeys = [], $merchantIds)
    {
        $this->creator = $creator;
        $this->tenant  = $creator->tenant;
        $this->insertedOrderKeys = $insertedOrderKeys;
        $this->merchantIds = $merchantIds;
        parent::__construct($input);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'order_code' => 'required',
        ];
    }

    protected function customValidate()
    {
        $code = trim($this->input('order_code'));
        if (in_array($code, $this->insertedOrderKeys)) {
            $this->errors()->add('order', static::ERROR_ALREADY_EXIST);
        }

        if(empty($this->merchantIds)) {
            $this->errors()->add('order_code', static::ERROR_INVALID);
        }

        $this->order = $this->tenant->orders()->where(['code' => $code])->whereIn('merchant_id', $this->merchantIds)->first();
        if(!$this->order instanceof Order) {
            $this->errors()->add('order_code', static::ERROR_INVALID);
        }

        if(
            $this->input['status'] == Order::STATUS_CANCELED &&
            empty($this->input['cancel_note'])
        ) {
            $this->errors()->add('cancel_note', static::ERROR_REQUIRED);
        }

    }

    /**
     * @return string
     */
    public function getOrderKey()
    {
        return $this->input['order_code'];
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }
}
