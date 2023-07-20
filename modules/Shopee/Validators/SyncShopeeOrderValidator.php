<?php

namespace Modules\Shopee\Validators;

use App\Base\Validator;
use Illuminate\Support\Arr;
use Modules\Product\Models\SkuCombo;
use Modules\Service;
use Modules\Store\Models\Store;

class SyncShopeeOrderValidator extends Validator
{
    /**
     * @var Store
     */
    protected $store;

    /**
     * SyncShopeeOrderValidator constructor
     *
     * @param Store $store
     * @param array $input
     */
    public function __construct(Store $store, array $input)
    {
        $this->store = $store;
        parent::__construct($input);
    }

    protected function customValidate()
    {
        $skus = Service::shopee()->findSkusByVariations($this->store, $this->input['items']);

        $invalidItems = [];
        $syncProduct  = false;
        $itemIds = [];
        foreach ($this->input['items'] as $item) {
            $code = (!empty($item['model_sku'])) ? trim($item['model_sku']) : $item['model_id'];
            $skuCombo = SkuCombo::query()->where('code', $code)->first();
            if($skuCombo) {
                continue;
            }

            if (empty($skus[$item['model_id']])) {
                $syncProduct = true;
                $itemIds[] = $item['item_id'];
            }
        }

        if($syncProduct) { // nếu phải đồng bộ lại sản phẩm thì thực hiện validate lại
            //nếu sku chưa được map thì tự động đồng bộ sản phẩm đó về
            Service::shopee()->syncProduct($this->store->id, $itemIds);

            $skus = Service::shopee()->findSkusByVariations($this->store, $this->input['items']);
            foreach ($this->input['items'] as $item) {
                if (empty($skus[$item['model_id']])) {
                    $invalidItems[] = Arr::only($item, ['model_id', 'model_name', 'model_sku']);
                }
            }
        }

        if (!empty($invalidItems)) {
            $this->errors()->add('invalid_items', $invalidItems);
            return false;
        }

        return true;
    }
}
