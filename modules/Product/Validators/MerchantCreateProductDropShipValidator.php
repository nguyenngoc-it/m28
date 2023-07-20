<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Illuminate\Support\Arr;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;

class MerchantCreateProductDropShipValidator extends Validator
{
    /** @var Product */
    protected $merchantProduct;
    protected $skus    = [];
    protected $options = [];


    public function rules()
    {
        return [
            'name' => 'required|string',
            'code' => 'required|string',
            'category_id' => 'int',
            'files' => 'array|max:5',
            'files.*' => 'file|mimes:jpg,jpeg,png|max:5120',
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


    protected function customValidate()
    {
        $code = trim($this->input('code'));
        if ($code && $this->user->merchant->products->where('code', $code)->first()) {
            $this->errors()->add('code', static::ERROR_ALREADY_EXIST);
            return;
        }
        $categoryId = $this->input('category_id');
        if ($categoryId && !Category::find($categoryId)) {
            $this->errors()->add('category_id', static::ERROR_EXISTS);
            return;
        }


        $skus = (array)Arr::get($this->input, 'skus');
        $skuCodes = [];
        foreach ($skus as $sku) {
            if(is_string($sku)){
                $sku = @json_decode($sku, true);
            }

            if(!empty($sku['code'])) {
                $code = trim($sku['code']);
                $otherSku = $this->user->merchant->skus->where('code', $code)->first();

                if($otherSku instanceof Sku) {
                    $this->errors()->add('sku_code_exist', $code);
                    return;
                }

                if(in_array($code, $skuCodes)) {
                    $this->errors()->add('sku_code_duplicated', $code);
                    return;
                }

                $skuCodes[]  = $code;
                $sku['code'] = $code;
            }

            $this->skus[] = $sku;
        }

        $options = (array)Arr::get($this->input, 'options');
        foreach ($options as $option) {
            if (is_string($option)) {
                $option = @json_decode($option, true);
            }
            $this->options[] = $option;
        }
    }
}
