<?php

namespace Modules\Merchant\Validators;

use App\Base\Validator;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Product;

class DetailExternalMerchantProductValidator extends Validator
{
    /** @var Product $product */
    protected $product;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'merchant_code' => 'required',
            'product_code' => 'required',
        ];
    }

    /**
     * @return Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    protected function customValidate()
    {
        $merchantCode = trim($this->input('merchant_code'));
        $merchant     = Merchant::query()->where([
            'tenant_id' => $this->user->tenant_id,
            'code' => $merchantCode,
            'creator_id' => $this->user->id
        ])->first();
        if (empty($merchant)) {
            $this->errors()->add('merchant_code', static::ERROR_EXISTS);
            return;
        }

        $productCode   = $this->input('product_code');
        $this->product = Product::query()->where([
            'tenant_id' => $this->user->tenant_id,
            'merchant_id' => $merchant->id,
            'code' => $productCode
        ])->first();
        if (empty($this->product) || ($this->product->creator_id !== $this->user->id)) {
            $this->errors()->add('product_code', static::ERROR_EXISTS);
            return;
        }
    }
}
