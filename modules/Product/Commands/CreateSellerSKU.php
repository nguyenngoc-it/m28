<?php

namespace Modules\Product\Commands;

use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuPrice;
use Modules\User\Models\User;

class CreateSellerSKU
{
    /**
     * @var array
     */
    protected $dataSku = [];

    /**
     * @var User|null
     */
    protected $creator = null;


    /**
     * @var Product|null
     */
    protected $product = null;

    /**
     * CreateSKU constructor.
     * @param Product $product
     * @param array $dataSku
     * @param User $creator
     */
    public function __construct(Product $product, array $dataSku, User $creator)
    {
        $this->dataSku = $dataSku;
        $this->creator = $creator;
        $this->product = $product;
    }

    public function handle()
    {
        $price = $this->dataSku['price'] === '' ? null : round((float)$this->dataSku['price'], 2);
        $this->dataSku['price'] = $price;
        $input = $this->makeInput();
        /** @var Sku $sku */
        $sku = $this->product->skus()->create($input);
        /**
         * Thêm tạm bảng giá cho chính seller tạo ra
         */
        SkuPrice::updateOrCreate(
            [
                'merchant_id' => $this->creator->merchant->id,
                'sku_id' => $sku->id,
            ],
            [
                'cost_price' => $this->dataSku['price'],
                'wholesale_price' => $this->dataSku['price'],
                'retail_price' => $this->dataSku['price'],
            ]
        );

        return $sku;
    }

    /**
     * Tạo SKU
     * @return array
     */
    protected function makeInput()
    {
        return [
            'tenant_id' => $this->product->tenant_id,
            'merchant_id' => $this->product->merchant_id,
            'category_id' => $this->product->category_id,
            'unit_id' => $this->product->unit_id,
            'creator_id' => $this->creator->id,
            'status' => Sku::STATUS_ON_SELL,
            'code' => $this->product->code,
            'name' => $this->product->name,
            'cost_price' => $this->dataSku['price'],
            'wholesale_price' => $this->dataSku['price'],
            'retail_price' => $this->dataSku['price'],
            'weight' => $this->dataSku['weight'],
            'length' => $this->dataSku['length'],
            'width' => $this->dataSku['width'],
            'height' => $this->dataSku['height'],
        ];
    }
}
