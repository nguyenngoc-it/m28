<?php

namespace Modules\Product\Commands;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuPrice;
use Modules\Product\Services\ProductEvent;
use Modules\Product\Services\SkuEvent;
use Modules\User\Models\User;

class CreateSKU
{
    /**
     * @var array
     */
    protected $input = [];

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
     * @param array $input
     * @param User $creator
     */
    public function __construct(Product $product, array $input, User $creator)
    {
        $this->input   = $input;
        $this->creator = $creator;
        $this->product = $product;
    }

    /**
     * @return Sku
     */
    public function handle()
    {
        $input = $this->makeInput();

        /** @var Sku $sku */
        $sku = $this->product->skus()->create($input);

        if(empty($this->input['code'])) {
            $this->makeCode($sku);
        }

        $this->createSkuPrice($sku);

        $sku->logActivity(SkuEvent::SKU_CREATE, $this->creator);

        if($sku->product instanceof Product) {
            $sku->product->logActivity(ProductEvent::SKU_CREATE, $this->creator, [
                'sku' => $sku->only(['id', 'name', 'code'])
            ]);
        }

        return $sku;
    }

    /**
     * @param Sku $sku
     */
    protected function makeCode(Sku $sku)
    {
        $sku->code = $this->product->code . '-' . $sku->id;
        $sku->save();
    }

    /**
     * Tạo SKU
     * @return array
     */
    protected function makeInput()
    {
        $input = array_merge($this->input, [
            'merchant_id' => $this->product->merchant_id,
            'tenant_id' => $this->product->tenant_id,
            'supplier_id' => $this->product->supplier_id,
            'category_id' => $this->product->category_id,
            'unit_id' => $this->product->unit_id,
            'creator_id' => $this->creator->id,
        ]);

        foreach (['weight', 'height', 'width', 'length', 'status'] as $p) {
            if(!isset($this->input[$p])) {
                $input[$p] = $this->product->{$p};
            }
        }

        if (empty($input['code'])) {
            $input['code'] = Str::random(30); //khởi tạo mã mặc định nếu k nhập mã, vì tạo k được trống mã
        }

        return $input;
    }

    /**
     * @param Sku $sku
     */
    protected function createSkuPrice(Sku $sku)
    {
        $skuPrices = Arr::pull($this->input, 'sku_prices', []);

        if (empty($skuPrices)) return;

        foreach ($skuPrices as $skuPrice) {
            $input           = Arr::only($skuPrice, ['merchant_id', 'cost_price', 'wholesale_price', 'retail_price']);
            $input['sku_id'] = $sku->id;
            SkuPrice::create($input);
        }
    }
}
