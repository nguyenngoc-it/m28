<?php

namespace Modules\Product\Commands;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductPrice;
use Modules\User\Models\User;
class CreateProductPrice
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
     * CreateProductPrice constructor.
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
     * @return ProductPrice
     */
    public function handle()
    {
        $productPrice = DB::transaction(function(){
            /** @var ProductPrice $productPrice */
            $productPrice = $this->product->productPrices()->create([
                'creator_id' => $this->creator->id,
                'tenant_id' => $this->product->tenant_id,
                'type'      => $this->input['type'],
                'status'    => ProductPrice::STATUS_WAITING_CONFIRM
            ]);

            foreach ($this->input['prices'] as $price) {
                $totalPrice = Arr::get($price, 'cost_price', 0) +
                              Arr::get($price, 'service_packing_price', 0) +
                              Arr::get($price, 'service_shipping_price', 0);

                    $productPrice->priceDetails()->create(array_merge($price, [
                    'tenant_id' => $this->product->tenant_id,
                    'total_price' => $totalPrice
                ]));
            }

            if($this->product->status == Product::STATUS_WAITING_FOR_QUOTE) {
                $this->product->status = Product::STATUS_WAITING_CONFIRM;
                $this->product->save();
            }

            return $productPrice->refresh();
        });


        return $productPrice;
    }
}
