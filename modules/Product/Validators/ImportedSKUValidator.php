<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Modules\Category\Models\Category;
use Modules\Product\Models\Unit;
use Modules\Tenant\Models\Tenant;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;

class ImportedSKUValidator extends Validator
{
    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var Unit
     */
    protected $unit;

    /**
     * @var Category
     */
    protected $category;

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
    protected $insertedSkuKeys = [];

    /**
     * ImportedSKUValidator constructor.
     * @param Tenant $tenant
     * @param array $input
     * @param array $insertedSkuKeys
     */
    public function __construct(Tenant $tenant, array $input, $insertedSkuKeys = [])
    {
        $this->tenant = $tenant;
        $this->insertedSkuKeys = $insertedSkuKeys;
        parent::__construct($input);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'sku_code' => 'required',
            'sku_name' => 'required',
            'cost_price' => 'numeric|gte:0',
            'wholesale_price' => 'numeric|gte:0',
            'retail_price' => 'numeric|gte:0',
            'stock' => 'numeric'
        ];
    }

    protected function customValidate()
    {
        $sku_code = $this->input('sku_code');
        if (
            ($unit_code = $this->input('unit_code')) &&
            !$this->unit = $this->tenant->units()->firstWhere('code', $unit_code)
        ) {
            $this->errors()->add('unit_code', static::ERROR_NOT_EXIST);
        }

        if (
            ($category_code = $this->input('category_code')) &&
            !$this->category = $this->tenant->categories()->firstWhere('code', $category_code)) {
            $this->errors()->add('category_code', static::ERROR_NOT_EXIST);
        }

        if (
            in_array($this->getSkuKey(), $this->insertedSkuKeys) ||
            $this->tenant->skus()->firstWhere('code', $sku_code)
        ) {
            $this->errors()->add('sku', static::ERROR_ALREADY_EXIST);
        }
    }

    /**
     * @return string
     */
    public function getSkuKey()
    {
        return $this->input['sku_code'];
    }

    /**
     * @return Unit
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }
}