<?php

namespace Modules\Shopee\Commands;

use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use Modules\Marketplace\Services\Marketplace;
use Modules\Marketplace\Services\MarketplaceException;
use Modules\Product\Models\Sku;
use Modules\Service;

class SyncShopeeProduct extends SyncShopeeProductBase
{
    /**
     * @return void
     * @throws RestApiException
     * @throws MarketplaceException
     */
    public function handle()
    {
        $itemBaseInfo = $this->store->shopeeApi()->getItemBaseInfo([
            'item_id_list' => $this->shopeeItemIds
        ])->getData();
        $response     = !empty($itemBaseInfo['response']) ? $itemBaseInfo['response'] : [];
        $itemLists    = !empty($response['item_list']) ? $response['item_list'] : [];
        if (empty($itemLists)) {
            $this->logger->info('items not found ', $response);
            return;
        }
        $this->logger->info('items', $itemLists);

        $itemModels         = [];
        $itemTierVariations = [];
        foreach ($itemLists as $item) {
            $itemId   = Arr::get($item, 'item_id');
            $itemName = Arr::get($item, 'item_name', '');
            $hasModel = Arr::get($item, 'has_model');
            $description = Arr::get($item, 'description', '');
            if(empty($description)) {
                $description = Arr::get($item, 'description_info.extended_description.field_list.0.text', '');
            }

            $models   = [];

            /**
             * Sản phẩm có biến thể
             */
            if ($hasModel) {
                if (!isset($itemModels[$itemId])) {
                    $modelList = $this->store->shopeeApi()->getModelList([
                        'item_id' => $itemId
                    ])->getData();

                    $this->logger->info('mode-items', $modelList);
                    $response                    = !empty($modelList['response']) ? $modelList['response'] : [];
                    $modelArray                  = !empty($response['model']) ? $response['model'] : [];
                    $tierVariationArray          = !empty($response['tier_variation']) ? $response['tier_variation'] : [];
                    $itemModels[$itemId]         = $modelArray;
                    $itemTierVariations[$itemId] = $tierVariationArray;
                }
                $models             = $itemModels[$itemId];
                $itemTierVariations = $itemTierVariations[$itemId];
            }

            /**
             * Sản phẩm không có biến thể sẽ tạo biến thể mặc định giống product
             */
            if (empty($models)) {
                $models   = [];
                $models[] = $this->makeModelDefault($item);
            }

            /**
             * Tạo sản phẩm từ biến thể shopee
             */
            foreach ($models as $model) {
                $model      = $this->getModelPrice($model);
                $tierIndexs = Arr::get($model, 'tier_index', []);
                if (!empty($tierIndexs) && !empty($itemTierVariations)) {
                    $model = $this->getModelName($itemName, $model, $tierIndexs, $itemTierVariations);
                }

                $data3rdResource = [
                    'name' => Arr::get($model, 'model_name', ''),
                    'price' => Arr::get($model, 'current_price', 0),
                    'original_price' => Arr::get($model, 'original_price', 0),
                    'code' => Arr::get($model, 'model_sku') ?: Arr::get($model, 'model_id', ''),
                    'sku_id_origin' => Arr::get($model, 'model_id'),
                    'source' => Marketplace::CODE_SHOPEE,
                    'product_id_origin' => $itemId,
                    'description' => $description,
                    'images' => Arr::get($item, 'image.image_url_list', []),
                    'weight' => Arr::get($item, 'weight', 0),
                    "status" => Arr::get($item, 'item_status', '') == 'NORMAL' ? Sku::STATUS_ON_SELL : Sku::STATUS_STOP_SELLING
                ];

                Service::product()->createProductFrom3rdPartner($this->store, $data3rdResource);
            }
        }
    }
}
