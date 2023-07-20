<?php

namespace Modules\Stock\Validators;

use App\Base\Validator;
use Modules\Stock\Models\Stock;
use Modules\Warehouse\Models\Warehouse;

class ChangingStockValidator extends Validator
{
    /** @var array $dataStocks */
    protected $dataStocks;
    /** @var array $errorStocks */
    protected $errorStocks = [];
    /** @var Warehouse */
    protected $warehouse;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'warehouse_id' => 'required',
            'warehouse_area_id' => 'required',
            'stocks' => 'required|array', // [['stock_id' => {}, 'quantity' => {}, 'warehouse_area_id' => {}]]
        ];
    }

    /**
     * @return array
     */
    public function getDataStocks(): array
    {
        return $this->dataStocks;
    }

    /**
     * @return array
     */
    public function getErrorStocks(): array
    {
        return $this->errorStocks;
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
        $warehouseId      = $this->input('warehouse_id');
        $warehouseAreaId  = $this->input('warehouse_area_id');
        $this->dataStocks = $this->input('stocks', []);

        $this->warehouse = Warehouse::query()->where([
            'tenant_id' => $this->user->tenant_id,
            'id' => $warehouseId
        ])->first();
        if (empty($this->warehouse)) {
            $this->errors()->add('warehouse_id', static::ERROR_EXISTS);
            return;
        }
        if (!in_array($warehouseAreaId, $this->warehouse->warehouseAreas->pluck('id')->all())) {
            $this->errors()->add('warehouse_area_id', static::ERROR_EXISTS);
            return;
        }

        $this->errorWhenValidateStocks($this->warehouse);
    }

    /**
     * @param Warehouse $warehouse
     * @return void
     */
    protected function errorWhenValidateStocks(Warehouse $warehouse)
    {
        foreach ($this->dataStocks as $key => $dataStock) {
            $stock = Stock::find($dataStock['stock_id']);
            if (empty($stock)) {
                unset($this->dataStocks[$key]);
                $this->errorStocks[] = ['stock_id' => $dataStock['stock_id'], 'message' => 'stock_invalid'];
                continue;
            }
            if ($dataStock['quantity'] < 1 || $dataStock['quantity'] > $stock->quantity) {
                unset($this->dataStocks[$key]);
                $this->errorStocks[] = ['stock_id' => $dataStock['stock_id'], 'message' => 'quantity_invalid'];
                continue;
            }
            if (!in_array($dataStock['warehouse_area_id'], $warehouse->warehouseAreas->pluck('id')->all())
                || $dataStock['warehouse_area_id'] == $stock->warehouse_area_id
            ) {
                unset($this->dataStocks[$key]);
                $this->errorStocks[] = ['stock_id' => $dataStock['stock_id'], 'message' => 'warehouse_area_invalid'];
            }
        }
    }
}
