<?php

namespace Modules\Order\Validators;

use App\Base\Validator;
use Modules\Category\Models\Category;
use Modules\Order\Models\Order;
use Modules\Product\Models\Sku;
use Modules\Product\Models\Unit;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;

class ReImportOrderValidator extends Validator
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
     * @var Sku
     */
    protected $sku;

    /**
     * @var Warehouse
     */
    protected $warehouse;

    /**
     * @var WarehouseArea
     */
    protected $warehouseArea;


    /**
     * @var Order
     */
    protected $order;

    /**
     * @var array
     */
    protected $merchantIds = [];

    /**
     * ReImportOrderValidator constructor.
     * @param User $creator
     * @param array $input
     * @param $merchantIds
     */
    public function __construct(User $creator, array $input, $merchantIds)
    {
        $this->creator = $creator;
        $this->tenant  = $creator->tenant;
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
            'sku_code' => 'required',
            'warehouse_code' => 'required',
            'warehouse_area_code' => 'required',
            'quantity' => 'required|numeric|gt:0',
        ];
    }

    protected function customValidate()
    {
        $order_code = trim($this->input('order_code'));

        if(empty($this->merchantIds)) {
            $this->errors()->add('order_code', static::ERROR_INVALID);
        }

        $this->order = $this->tenant->orders()->where(['code' => $order_code])->whereIn('merchant_id', $this->merchantIds)->first();
        if(!$this->order instanceof Order) {
            $this->errors()->add('order_code', static::ERROR_INVALID);
        }

        if (
            ($warehouseCode = $this->input('warehouse_code'))
            && !($this->warehouse = $this->tenant->warehouses()->where('code', $warehouseCode)->where('status', true)->first())
        ) {
            $this->errors()->add('warehouse_code', static::ERROR_NOT_EXIST);
        }

        if (
            $this->warehouse
            && ($warehouseAreaCode = $this->input('warehouse_area_code'))
            && !($this->warehouseArea = $this->warehouse->areas()->firstWhere('code', $warehouseAreaCode))
        ) {
            $this->errors()->add('warehouse_area_code', static::ERROR_NOT_EXIST);
        }

        if (
            !$this->order ||
            (
                ($skuCode = $this->input('sku_code'))
                && !($this->sku = $this->order->skus()->firstWhere('code', $skuCode))
            )
        ) {
            $this->errors()->add('sku_code', static::ERROR_NOT_EXIST);
        }
    }


    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }


    /**
     * @return Sku
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * @return Warehouse|null
     */
    public function getWarehouse()
    {
        return $this->warehouse;
    }

    /**
     * @return WarehouseArea|null
     */
    public function getWarehouseArea()
    {
        return $this->warehouseArea;
    }

}