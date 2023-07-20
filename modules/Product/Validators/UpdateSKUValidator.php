<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Modules\Auth\Services\Permission;
use Modules\Category\Models\Category;
use Illuminate\Support\Arr;
use Modules\Product\Models\Sku;
use Modules\Product\Models\Unit;
use Modules\Tenant\Models\Tenant;

class UpdateSKUValidator extends Validator
{
    /**
     * @var Tenant|null
     */
    protected $tenant = null;

    /** @var Sku $sku */
    protected $sku;

    /**
     * CreateSKUValidator constructor.
     * @param array $input
     * @param Sku $sku
     */
    public function __construct(array $input = [], Sku $sku)
    {
        $newInput = static::removeNullPrice($input);
        parent::__construct($newInput);
        $this->tenant = $sku->tenant;
        $this->sku = $sku;
    }

    /**
     * @var string[]
     */
    public static $acceptKeys = [
        'name',
        'barcode',
        'unit_id',
        'category_id',
        'color',
        'size',
        'type',
        'options',
        'description',
        'sku_prices',
        'fobiz_code'
    ];

    /**
     * @return array|string[]
     */
    public function rules()
    {
        return [
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
    public static function removeNullPrice(array $input)
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
        if (!array_intersect($this->sku->product->merchants->pluck('id')->all(), $this->user->merchants->pluck('id')->all())
            && !$this->user->can(Permission::PRODUCT_MANAGE_ALL))
        {
            $this->errors()->add('code', 'not_to_access_product');
            return;
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

        if (isset($this->input['fobiz_code']) && $this->tenant->skus()->firstWhere('fobiz_code', $this->input['fobiz_code'])) {
            $this->errors()->add('fobiz_code', static::ERROR_ALREADY_EXIST);
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
