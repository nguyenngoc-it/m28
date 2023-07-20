<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Illuminate\Support\Arr;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductPrice;
use Modules\Product\Models\Sku;
use Modules\Tenant\Models\Tenant;
use function Clue\StreamFilter\fun;

class CreateProductPriceValidator extends Validator
{
    /**
     * @var Tenant|null
     */
    protected $tenant = null;

    /**
     * @var Product|null
     */
    protected $product = null;

    /**
     * CreateSKUValidator constructor.
     * @param array $input
     * @param Product $product
     */
    public function __construct(array $input = [], Product $product)
    {
        $newInput = $this->removeNullPrice($input);
        parent::__construct($newInput);
        $this->tenant  = $product->tenant;
        $this->product = $product;
    }

    /**
     * @var string[]
     */
    public static $acceptKeys = [
        'type',
        'prices'
    ];

    /**
     * @return array|string[]
     */
    public function rules()
    {
        return [
            'type' => 'required|in:'.ProductPrice::TYPE_COMBO.','.ProductPrice::TYPE_SKU,
            'prices' => 'array',
            'prices.*.cost_price' => 'numeric|gte:0',
            'prices.*.service_packing_price' => 'numeric|gte:0',
            'prices.*.service_shipping_price' => 'numeric|gte:0',
            'prices.*.combo'  => 'numeric|gt:0',
            'prices.*.sku_id' => 'numeric|gt:0',
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
        $prices = Arr::get($input, 'prices', []);
        if (count($prices) == 0) return $input;

        $prices = array_map(function ($price) {
            return array_filter($price, function ($value) { return $value !== null; });
        }, $prices);

        $input['prices'] = $prices;
        return $input;
    }

    protected function customValidate()
    {
        $type   = Arr::get($this->input, 'type');
        $prices = Arr::get($this->input, 'prices', []);
        foreach ($prices as $price) {
            if($type == ProductPrice::TYPE_COMBO && empty($price['combo'])) {
                $this->errors()->add('combo', self::ERROR_INVALID);
                return;
            }

            if($type == ProductPrice::TYPE_SKU) {
                if(empty($price['sku_id'])) {
                    $this->errors()->add('sku_id', self::ERROR_INVALID);
                    return;
                }
                $sku = $this->product->skus()->firstWhere('id', $price['sku_id']);
                if(!$sku instanceof Sku) {
                    $this->errors()->add('sku_id', self::ERROR_INVALID);
                    return;
                }
            }
        }
    }
}
