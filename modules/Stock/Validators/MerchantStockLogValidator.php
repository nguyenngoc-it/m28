<?php

namespace Modules\Stock\Validators;

use App\Base\Validator;
use Illuminate\Support\Arr;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;

class MerchantStockLogValidator extends Validator
{
    /**
     * @var Merchant
     */
    protected $merchant;

    /**
     * @var array
     */
    protected $input = [];

    /**
     * MerchantStockLogValidator constructor.
     * @param Merchant $merchant
     * @param array $input
     */
    public function __construct(Merchant $merchant, array $input)
    {
        $this->merchant   = $merchant;
        parent::__construct($input);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'product_id' => 'numeric',
            'sku_id' => 'numeric',
            'without_actions' => 'array'
        ];
    }

    protected function customValidate()
    {
        $product = null;
        $skuId   = Arr::get($this->input, 'sku_id');
        if(!empty($skuId)) {
            $sku = $this->merchant->tenant->skus()->firstWhere('id', $skuId);
            if(!$sku instanceof Sku) {
                $this->errors()->add('sku_id', static::ERROR_INVALID);
            }
            $product = $sku->product;
        }

        $productId = Arr::get($this->input, 'product_id');
        if(!empty($productId)) {
            $product = $this->merchant->tenant->products()->firstWhere('id', $productId);
            if(!$product instanceof Product) {
                $this->errors()->add('product_id', static::ERROR_INVALID);
            }
        }

        if($product instanceof Product) {
            $productMerchantIds = $product->productMerchants()->pluck('merchant_id')->toArray();
            if(!in_array($this->merchant->id, $productMerchantIds)) {
                $this->errors()->add('merchant', static::ERROR_INVALID);
            }
        }
    }
}
