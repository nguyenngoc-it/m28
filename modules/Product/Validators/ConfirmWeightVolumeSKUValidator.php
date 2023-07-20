<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Modules\Product\Models\Product;

class ConfirmWeightVolumeSKUValidator extends Validator
{
    /** @var Product $product */
    protected $product;
    /** @var array */
    protected $skuIds;

    /**
     * CreateSKUValidator constructor.
     * @param Product $product
     * @param array $input
     */
    public function __construct(Product $product, array $input = [])
    {
        parent::__construct($input);
        $this->product = $product;
        $this->skuIds  = $this->input('sku_ids', []);
    }

    /**
     * @return array|string[]
     */
    public function rules()
    {
        return [
            'sku_ids' => 'array',
        ];
    }

    /**
     * @return array
     */
    public function getSkuIds(): array
    {
        return $this->skuIds;
    }
}
