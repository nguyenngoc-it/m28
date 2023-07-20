<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Modules\Order\Models\Order;
use Modules\Warehouse\Models\Warehouse;

class CreatingDocumentImportingReturnGoodsValidator extends Validator
{
    /**
     * @var Warehouse
     */
    protected $warehouse;

    /**
     * @var array
     */
    protected $skuData;

    /**
     * @var array
     */
    protected $objects;

    /**
     * order_items => [{id:1, skus: [{id:1, quantity:1}]}]
     *
     * @return array
     */
    public function rules()
    {
        return [
            'warehouse_id' => "required",
            'order_items' => 'required|array',
            'note' => 'string'
        ];
    }

    /**
     * @return Warehouse
     */
    public function getWarehouse(): Warehouse
    {
        return $this->warehouse;
    }

    protected function customValidate()
    {
        if (!$this->warehouse = $this->user->tenant->warehouses()->firstWhere(['id' => $this->input['warehouse_id']])) {
            $this->errors()->add('warehouse_id', static::ERROR_EXISTS);
            return;
        }

        $this->validateOrders();
    }

    /**
     * @return void
     */
    protected function validateOrders()
    {
        $orderItems = $this->input('order_items');
        foreach ($orderItems as $orderItem) {
            /** @var Order $order */
            if ($order = Order::find($orderItem['id'])) {
                $diff = array_diff($order->orderSkus->pluck('sku_id')->all(), collect($orderItem['skus'])->pluck('id')->all());
                if ($diff) {
//                    $this->errors()->add('order_items', [
//                        'skus_invalid' => ['order_id' => $orderItem['id']]
//                    ]);
//                    break;
                }
            } else {
                $this->errors()->add('order_items', [
                    'not_found_order' => ['order_id' => $orderItem['id']]
                ]);
                break;
            }
        }
    }
}
