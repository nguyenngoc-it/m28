<?php

namespace Modules\Warehouse\Validators;

use App\Base\Validator;
use Modules\Stock\Models\Stock;
use Modules\Store\Models\Store;
use Modules\Warehouse\Models\Warehouse;

class ChangeStateWarehouseValidator extends Validator
{
    /**
     * ChangeStateWarehouseValidator constructor.
     * @param Warehouse $warehouse
     * @param $input
     */
    public function __construct(Warehouse $warehouse, $input)
    {
        $this->warehouse = $warehouse;
        parent::__construct($input);
    }


    /**
     * @var Warehouse
     */
    protected $warehouse;


    /**
     * @return array
     */
    public function rules()
    {
        return [
            'status' => 'required',
        ];
    }

    protected function customValidate()
    {
        $status = $this->input['status'];
        if(!$status) {
            $warehouseAreaIds = $this->warehouse->areas()->pluck('id')->toArray();
            if(!empty($warehouseAreaIds)) {
                $stockCount = Stock::query()->where('tenant_id', $this->warehouse->tenant_id)
                    ->whereIn('warehouse_area_id', $warehouseAreaIds)
                    ->where('real_quantity', '>', 0)->count();
                if($stockCount > 0) {
                    $this->errors()->add('stock', static::ERROR_INVALID);
                    return;
                }
            }

            $storeCount = Store::query()->where('tenant_id', $this->warehouse->tenant_id)
                ->where('warehouse_id', $this->warehouse->id)
                ->where('status', Store::STATUS_ACTIVE)->count();
            if($storeCount > 0) {
                $this->errors()->add('store', static::ERROR_EXISTS);
                return;
            }
        }

    }
}