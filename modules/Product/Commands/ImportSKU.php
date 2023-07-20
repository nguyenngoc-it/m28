<?php

namespace Modules\Product\Commands;

use Illuminate\Support\Arr;
use Modules\Category\Models\Category;
use Modules\Product\Models\Sku;
use Modules\Product\Models\Unit;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

class ImportSKU
{
    /**
     * @var array
     */
    protected $input;

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
     * @var User
     */
    protected $creator;

    /**
     * Số lượng option trong file
     * @var int
     */
    protected $optionNumber = 3;

    /**
     * ImportSKU constructor.
     * @param array $input
     */
    public function __construct(array $input)
    {
        $this->tenant = Arr::pull($input, 'tenant');
        $this->unit = Arr::pull($input, 'unit');
        $this->category = Arr::pull($input, 'category');
        $this->creator = Arr::pull($input, 'creator');
        $this->input = $input;
    }

    /**
     * @return Sku
     */
    public function handle()
    {
        $code = trim($code = $this->input['sku_code']);
        $sku = $this->tenant->skus()->firstOrCreate(['code' => $code], [
            'unit_id' => ($this->unit) ? $this->unit->id : 0,
            'category_id' => ($this->category) ? $this->category->id : 0,
            'name' => trim($this->input['sku_name']),
            'barcode' => trim($this->input['barcode']),
            'color' => $this->input['color'],
            'size' => $this->input['size'],
            'type' => $this->input['type'],
            'options' => $this->getSkuOptions(),
            'status' => Sku::STATUS_ON_SELL,
            'creator_id' => $this->creator->id
        ]);

        return $sku;
    }

    /**
     * @return array
     */
    protected function getSkuOptions()
    {
        $options = [];
        for ($i = 1; $i <= $this->optionNumber; $i++) {
            if ($param = $this->input["option_{$i}"]) {
                $options[$param] = $this->input["option_{$i}_value"];
            }
        }

        return $options;
    }
}