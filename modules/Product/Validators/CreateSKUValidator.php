<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Illuminate\Support\Arr;
use Modules\Category\Models\Category;
use Modules\Product\Models\Sku;
use Modules\Product\Models\Unit;
use Modules\Tenant\Models\Tenant;
use function Clue\StreamFilter\fun;

class CreateSKUValidator extends Validator
{
    /**
     * @var Tenant|null
     */
    protected $tenant = null;

    /**
     * CreateSKUValidator constructor.
     * @param array $input
     * @param Tenant $tenant
     */
    public function __construct(array $input = [], Tenant $tenant)
    {
        $newInput = $this->removeNullPrice($input);
        parent::__construct($newInput);
        $this->tenant = $tenant;
    }

    /**
     * @var string[]
     */
    public static $acceptKeys = [
        'code',
        'name',
        'barcode',
        'unit_id',
        'category_id',
        'color',
        'size',
        'type',
        'options',
        'description',
        'sku_prices'
    ];

    /**
     * @return array|string[]
     */
    public function rules()
    {
        return [
            'code' => 'required',
            'name' => 'required',
            'options' => 'array',
            'sku_prices' => 'array',
            'sku_prices.*.merchant_id' => 'required|exists:merchants,id',
            'sku_prices.*.cost_price' => 'numeric|gte:0',
            'sku_prices.*.wholesale_price' => 'numeric|gte:0',
            'sku_prices.*.retail_price' => 'numeric|gte:0',
        ];
    }

    /**
     * remove null prices before validate numeric type
     *
     * @param array $input
     * @return array|void
     */
    protected function removeNullPrice(array $input)
    {
        $skuPrices = Arr::get($input, 'sku_prices', []);
        if (count($skuPrices) == 0) return $input;

        $skuPrices = array_map(function ($skuPrice) {
            return array_filter($skuPrice, function ($value) { return $value !== null; });
        }, $skuPrices);

        $input['sku_prices'] = $skuPrices;
        return $input;
    }

    protected function customValidate()
    {
        $sku = Sku::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('code', $this->input['code'])
            ->first();
        if ($sku instanceof Sku) {
            $this->errors()->add('code', self::ERROR_ALREADY_EXIST);
            return;
        }

        if (isset($this->input['unit_id'])) {
            $unit = Unit::find($this->input['unit_id']);
            if (
                empty($unit) ||
                ($unit instanceof Unit && $unit->tenant_id != $this->tenant->id)
            ) {
                $this->errors()->add('unit_id', self::ERROR_INVALID);
                return;
            }
        }

        if (isset($this->input['category_id'])) {
            $category = Category::find($this->input['category_id']);
            if (
                empty($category) ||
                ($category instanceof Category && $category->tenant_id != $this->tenant->id)
            ) {
                $this->errors()->add('category_id', self::ERROR_INVALID);
                return;
            }
        }

        $skuPrices = Arr::get($this->input, 'sku_prices', []);
        $sellerIds = array_unique(array_map(function ($skuPrice) {
            return $skuPrice['merchant_id'];
        }, $skuPrices));

        if (count($skuPrices) > count($sellerIds)) {
            $this->errors()->add('merchant_id', self::ERROR_DUPLICATED);
            return;
        }
    }
}
