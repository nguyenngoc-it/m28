<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;

class CreatingProductInternalValidator extends Validator
{
    /** @var Product */
    protected $merchantProduct;

    public function rules()
    {
        return [
            'name' => 'required|string',
            'code' => 'string',
            'category_id' => 'int',
            'files' => 'array|max:5',
            'files.*' => 'file|mimes:jpg,jpeg,png|max:5120',
            'services' => 'array',
            'weight' => 'numeric',
            'height' => 'numeric',
            'width' => 'numeric',
            'length' => 'numeric'
        ];
    }

    protected function customValidate()
    {
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
    }
}
