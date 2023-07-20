<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Modules\Product\Models\Product;

class DetailMerchantProductValidator extends Validator
{
    /** @var Product */
    protected $merchantProduct;

    /**
     * @return array|string[]
     */
    public function rules()
    {
        return [
            'id' => 'required',
        ];
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
    }
}
