<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Illuminate\Support\Arr;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;

class UpdatingMerchantProductValidator extends Validator
{
    /** @var Product */
    protected $merchantProduct;
    protected $skus    = null;
    protected $options = null;

    public function rules()
    {
        return [
            'id' => 'required',
            'name' => 'string',
            'code' => 'string',
            'category_id' => 'int',
            'files' => 'array|max:5',
            'files.*' => 'file|mimes:jpg,jpeg,png|max:5120',
            'removed_files' => 'array',
            'services' => 'array',
            'options' => 'array',
            'skus' => 'array',
            'weight' => 'numeric',
            'height' => 'numeric',
            'width' => 'numeric',
            'length' => 'numeric'
        ];
    }

    public function getSkus()
    {
        return $this->skus;
    }

    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return Product
     */
    public function getMerchantProduct(): Product
    {
        return $this->merchantProduct;
    }


    protected function customValidate()
    {
        $this->merchantProduct = Product::query()->where([
            'tenant_id' => $this->user->tenant_id,
            'id' => $this->input('id', 0),
        ])->first();
        if (!$this->merchantProduct || !$this->user->merchant->productMerchants->where('product_id', $this->merchantProduct->id)->first()) {
            $this->errors()->add('id', static::ERROR_EXISTS);
            return;
        }
        if ($this->user->merchant->id != $this->merchantProduct->merchant_id) {
            $this->errors()->add('id', 'product_is_not_from_seller');
            return;
        }
        $code = $this->input('code');
        if ($code && $this->user->merchant->products->where('code', $code)->first()) {
            $this->errors()->add('code', static::ERROR_ALREADY_EXIST);
            return;
        }
        $categoryId = $this->input('category_id');
        if ($categoryId && !Category::find($categoryId)) {
            $this->errors()->add('category_id', static::ERROR_EXISTS);
            return;
        }

        $skus = Arr::get($this->input, 'skus');
        if($skus !== null) {
            $skuCodes   = [];
            $this->skus = [];
            foreach ($skus as $sku) {
                if(is_string($sku)){
                    $sku = @json_decode($sku, true);
                }
                $skuId = isset($sku['id']) ? $sku['id'] : 0;
                if(!empty($sku['code'])) {
                    $code = trim($sku['code']);
                    $otherSku = $this->user->merchant->skus->where('code', $code)
                        ->where('id', '!=', $skuId)
                        ->first();

                    if($otherSku instanceof Sku) {
                        $this->errors()->add('sku_code_exist', $code);
                        return;
                    }
                    if(in_array($code, $skuCodes)) {
                        $this->errors()->add('sku_code_duplicated', $code);
                        return;
                    }

                    $skuCodes[] = $code;
                    $sku['code'] = $code;
                }

                $this->skus[] = $sku;
            }
        }


        $options = Arr::get($this->input, 'options');
        if($options !== null) {
            $this->options = [];
            foreach ($options as $option) {
                if (is_string($option)) {
                    $option = @json_decode($option, true);
                }
                if(empty($option['values'])) {
                    $this->errors()->add('option_values', static::ERROR_REQUIRED);
                    return;
                }
                $this->options[] = $option;
            }
        }
    }
}
