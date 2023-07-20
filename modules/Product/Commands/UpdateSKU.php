<?php

namespace Modules\Product\Commands;

use Illuminate\Support\Arr;
use Modules\Category\Models\Category;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuPrice;
use Modules\Product\Models\Unit;
use Modules\Product\Services\ProductEvent;
use Modules\Product\Services\SkuEvent;
use Modules\Product\Validators\UpdateSKUValidator;
use Modules\User\Models\User;

class UpdateSKU
{
    /**
     * @var array
     */
    protected $input = [];

    /**
     * @var Sku|null
     */
    protected $sku = null;

    /**
     * @var User|null
     */
    protected $creator = null;

    /**
     * UpdateSKU constructor.
     * @param Sku $sku
     * @param array $input
     * @param User $creator
     */
    public function __construct(Sku $sku, array $input, User $creator)
    {
        $this->sku = $sku;
        $this->input = $input;
        $this->creator = $creator;
    }

    public function handle()
    {
        $skuPrices = Arr::pull($this->input, 'sku_prices', []);

        $payloadLogs = [];
        foreach ($this->input as $key => $value) {

            if (
                $value !== null &&
                in_array($key, UpdateSKUValidator::$acceptKeys) &&
                $this->sku->{$key} != $value
            ) {
                $old = $this->sku->{$key};
                $new = $value;
                if($key == 'category_id') {
                    $categoryOld = Category::find($this->sku->category_id);
                    $categoryNew = Category::find($value);
                    $old         = ($categoryOld instanceof Category) ? $categoryOld->name : '';
                    $new         = ($categoryNew instanceof Category) ? $categoryNew->name : '';
                }

                if($key == 'unit_id') {
                    $unitOld = Unit::find($this->sku->unit_id);
                    $unitNew = Unit::find($value);
                    $old     = ($unitOld instanceof Unit) ? $unitOld->name : '';
                    $new     = ($unitNew instanceof Unit) ? $unitNew->name : '';
                }

                $payloadLogs[$key]['old'] = $old;
                $payloadLogs[$key]['new'] = $new;
                $this->sku->{$key}   = (is_string($value)) ? trim($value) : $value;
            }
        }

        if(!empty($payloadLogs)) {
            $this->sku->logActivity(SkuEvent::SKU_UPDATE, $this->creator, $payloadLogs);

            $this->sku->product->logActivity(ProductEvent::SKU_UPDATE, $this->creator, [
                'data' => $payloadLogs,
                'sku' => $this->sku->only(['id', 'code', 'name', 'ref'])
            ]);

            $this->sku->save();
        }


        SkuPrice::query()->where('sku_id', $this->sku->id)->delete();

        foreach ($skuPrices as $skuPrice) {
            $input = Arr::only($skuPrice, ['merchant_id', 'cost_price', 'wholesale_price', 'retail_price']);
            $input['sku_id'] = $this->sku->id;
            SkuPrice::create($input);
        }

        return $this->sku;
    }
}
