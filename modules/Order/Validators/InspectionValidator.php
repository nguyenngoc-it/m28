<?php

namespace Modules\Order\Validators;

use App\Base\Validator;
use Illuminate\Support\Arr;
use Modules\Order\Models\Order;
use Modules\Service;

class InspectionValidator extends Validator
{
    /** @var Order $order */
    protected $order;

    /**
     * InspectionValidator constructor.
     * @param Order $order
     * @param array $input
     */
    public function __construct(Order $order, array $input)
    {
        $this->order = $order;
        parent::__construct($input);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'order_stocks' => 'required|array'
        ];
    }

    protected function customValidate()
    {
        if (!Service::order()->canInspection($this->order, $this->user)) {
            $this->errors()->add('order', self::ERROR_INVALID);
            return;
        }

        $orderStocks = $this->input('order_stocks', []);
        foreach ($orderStocks as $orderStock) {
            $skuId           = Arr::get($orderStock, 'sku_id');
            $warehouseAreaId = Arr::get($orderStock, 'warehouse_area_id');
            $quantity        = Arr::get($orderStock, 'quantity');
            if (empty($skuId) || empty($warehouseAreaId) || empty($quantity)) {
                $this->errors()->add('order_stock', static::ERROR_EXISTS);
                return;
            }
        }
    }
}
