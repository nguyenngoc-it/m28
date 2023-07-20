<?php

namespace Modules\Stock\Validators;

use App\Base\Validator;
use Modules\Product\Models\Sku;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;

class ImportedStockValidator extends Validator
{
    /**
     * @var Tenant
     */
    protected $tenant;

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
     * @var array
     */
    protected $insertedStockKeys = [];

    /**
     * ImportedStockValidator constructor.
     * @param User $user
     * @param array $input
     * @param array $insertedStockKeys
     */
    public function __construct(User $user, array $input, $insertedStockKeys = [])
    {
        $this->user   = $user;
        $this->tenant = $user->tenant;
        $this->insertedStockKeys = $insertedStockKeys;
        parent::__construct($input);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'import_code' => 'required',
            'sku_code' => 'required',
            'warehouse_code'  => 'required',
            'stock' => 'required|numeric'
        ];
    }

    protected function customValidate()
    {
        if (
            ($skuCode = $this->input('sku_code'))
            && !($this->sku = $this->tenant->skus()->firstWhere('code', $skuCode))
        ) {
            $this->errors()->add('sku_code', static::ERROR_NOT_EXIST);
            return;
        }

        if (
            ($warehouseCode = $this->input('warehouse_code'))
            && !($this->warehouse = $this->tenant->warehouses()->firstWhere('code', $warehouseCode))
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

        if($this->warehouse && !$this->warehouseArea) {
            $this->warehouseArea = $this->warehouse->getDefaultArea();
        }

        if(!$this->warehouse && $this->warehouseArea) {
            $this->warehouse = $this->warehouseArea->warehouse;
        }

        if($this->warehouse && !$this->warehouse->status) {
            $this->errors()->add('warehouse_code', static::ERROR_INVALID);
        }

        if (
            in_array($this->getStockKey(), $this->insertedStockKeys)
        ) {
            $this->errors()->add('sku', static::ERROR_ALREADY_EXIST);
        }
    }


    /**
     * @return string
     */
    public function getStockKey()
    {
        $skuCode = $this->input['sku_code'];
        $importCode = $this->input['import_code'];
        $warehouseCode = $this->input['warehouse_code'];
        $warehouseAreaCode = $this->input['warehouse_area_code'];
        return "$importCode-$skuCode-$warehouseCode-$warehouseAreaCode";
    }

    /**
     * @return Sku|null
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

    public function getMerchant()
    {
        return $this->merchant;
    }
}
