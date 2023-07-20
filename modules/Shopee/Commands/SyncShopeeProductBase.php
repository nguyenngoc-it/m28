<?php

namespace Modules\Shopee\Commands;

use Gobiz\Log\LogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Currency\Models\Currency;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Events\ProductCreated;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductMerchant;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuPrice;
use Modules\Product\Services\ProductEvent;
use Modules\Product\Services\SkuEvent;
use Modules\Service;
use Modules\Store\Models\Store;
use Modules\Store\Models\StoreSku;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;

abstract class SyncShopeeProductBase
{
    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var Store
     */
    protected $store;

    /**
     * @var array
     */
    protected $shopeeItemIds;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @var Merchant
     */
    protected $merchant;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SyncShopeeProduct constructor
     *
     * @param int $storeId
     * @param array $shopeeItemIds
     */
    public function __construct($storeId, $shopeeItemIds)
    {
        $this->storeId       = $storeId;
        $this->shopeeItemIds = $shopeeItemIds;
        $this->store         = Store::find($this->storeId);
        $this->merchant      = $this->store->merchant;
        $this->creator       = $this->merchant->user ? $this->merchant->user : Service::user()->getSystemUserDefault();
        $this->logger        = LogService::logger('shopee-sync-product', [
            'context' => ['shop_id' => $storeId, 'store' => $this->storeId, 'shopee_item_ids' => $shopeeItemIds],
        ]);
    }

    /**
     * Tạo biến thể mặc định giống product
     *
     * @param array $item
     * @return array
     */
    protected function makeModelDefault($item = [])
    {
        return [
            'model_id' => Arr::get($item, 'item_id'),
            'model_name' => Arr::get($item, 'item_name'),
            'price_info' => Arr::get($item, 'price_info'),
            'model_sku' => Arr::get($item, 'item_sku'),
        ];
    }

    /**
     * @param $model
     * @return mixed
     */
    protected function getModelPrice($model)
    {
        $priceInfos              = Arr::get($model, 'price_info', []);
        $priceInfo               = Arr::first($priceInfos);
        $model['current_price']  = Arr::get($priceInfo, 'current_price', 0);
        $model['original_price'] = Arr::get($priceInfo, 'original_price', 0);

        return $model;
    }

    /**
     * @param $itemName
     * @param $model
     * @param $tierIndexs
     * @param $itemTierVariations
     * @return mixed
     */
    protected function getModelName($itemName, $model, $tierIndexs, $itemTierVariations)
    {
        $modelName = $itemName;
        foreach ($tierIndexs as $key => $index) {
            if (isset($itemTierVariations[$key])) {
                $tierVariation = $itemTierVariations[$key];
                $name          = Arr::get($tierVariation, 'name');
                $optionList    = Arr::get($tierVariation, 'option_list');
                if (isset($optionList[$index])) {
                    $name = $name . ' ' . $optionList[$index]['option'];
                }
                $modelName = $modelName . ' - ' . $name;
            }
        }
        $model['model_name'] = $modelName;

        return $model;
    }

    /**
     * @param array $item
     * @param array $model
     * @return array
     */
    protected function makeDataProduct($model = [], $item = [])
    {
        $productName = Arr::get($item, 'item_name');
        $modelName   = Arr::get($model, 'model_name');
        if ($modelName && $productName != $modelName) {
            $productName = $productName . ' : ' . $modelName;
        }

        $modelId     = Arr::get($model, 'model_id');
        $modelCode   = Arr::get($model, 'model_sku');
        $image       = Arr::get($item, 'image', []);
        $description = Arr::get($item, 'description', '');
        if (empty($description)) {
            $descriptionObject   = Arr::get($item, 'description_info');
            $extendedDescription = Arr::get($descriptionObject, 'extended_description', []);
            $fieldList           = Arr::get($extendedDescription, 'field_list', []);
            foreach ($fieldList as $field) {
                $description = $description . ' ' . $field['text'];
            }
        }

        return [
            'source' => Product::SOURCE_SHOPEE,
            'code' => (!empty($modelCode)) ? trim($modelCode) : $modelId,
            'product_id_origin' => Arr::get($item, 'item_id'),
            'sku_id_origin' => $modelId,
            'name' => $productName,
            'description' => $description,
            'images' => Arr::get($image, 'image_url_list', []),
            'weight' => Arr::get($item, 'weight', 0),
        ];
    }


    /**
     * @param Sku $sku
     * @param array $model
     * @param array $item
     * @return Product
     */
    protected function updateProduct(Sku $sku, $model, array $item)
    {
        $data    = $this->makeDataProduct($model, $item);
        $product = $sku->product;
        $this->updateSku($sku, $model, $data);

        $changes = $product->getChanges();
        $this->logger->info('updated product ' . $product->code, ['data' => $data, 'change' => $changes]);
        if (!empty($changes)) {
            $product->logActivity(ProductEvent::UPDATE, $this->creator, array_merge($changes, ['source' => 'shopee']));
        }

        return $product;
    }

    /**
     * @param Sku $sku
     * @param $model
     * @param array $data
     */
    protected function updateSku(Sku $sku, $model, array $data)
    {
        $retailPrice = Arr::get($model, 'current_price');
        if (!$retailPrice) {
            $retailPrice = Arr::get($model, 'original_price');
        }

        $merchant = $this->merchant;
        $currency = ($merchant instanceof Merchant) ? $merchant->getCurrency() : null;

        if ($sku->retail_price != $retailPrice) {
            $sku->retail_price = $retailPrice;
            $sku->save();

            $dataOld  = [];
            $skuPrice = $sku->prices()->firstWhere('merchant_id', $sku->merchant_id);
            if (!$skuPrice instanceof SkuPrice) {
                $skuPrice = $sku->prices()->create([
                    'merchant_id' => $sku->merchant_id,
                    'retail_price' => $retailPrice
                ]);
            } else {
                $skuPriceOld = clone $skuPrice;
                $dataOld     = $skuPriceOld->attributesToArray();

                $skuPrice->update(['retail_price' => $retailPrice]);
            }

            $sku->logActivity(SkuEvent::SKU_UPDATE_PRICE, $this->creator, [
                'from' => $dataOld,
                'to' => $skuPrice->attributesToArray(),
                'merchant' => ($merchant instanceof Merchant) ? $merchant->only(['id', 'name', 'code']) : null,
                'currency' => ($currency instanceof Currency) ? $currency->attributesToArray() : null,
            ]);

            $sku->product->logActivity(ProductEvent::SKU_UPDATE_PRICE, $this->creator, [
                'sku' => $sku->only(['id', 'code', 'name']),
                'merchant' => ($merchant instanceof Merchant) ? $merchant->only(['id', 'name', 'code']) : null,
                'currency' => ($currency instanceof Currency) ? $currency->attributesToArray() : null,
                'from' => $dataOld,
                'to' => $skuPrice->attributesToArray(),
            ]);
        }

        $storeSkuCode     = Arr::get($data, 'sku_id_origin');
        $storeProductCode = Arr::get($data, 'product_id_origin');
        $storeSku         = $this->store->storeSkus()
            ->where('tenant_id', $merchant->tenant_id)
            ->where('sku_id_origin', $storeSkuCode)
            ->where('sku_id', $storeSkuCode)->first();

        if (!$storeSku instanceof StoreSku) {
            $this->store->storeSkus()->create([
                'tenant_id' => $sku->tenant_id,
                'code' => $storeSkuCode,
                'marketplace_code' => $this->store->marketplace_code,
                'marketplace_store_id' => $this->store->marketplace_store_id,
                'sku_id' => $sku->id,
                'sku_id_origin' => $storeSkuCode,
                'product_id' => $sku->product->id,
                'product_id_origin' => $storeProductCode
            ]);
        } else {
            $storeSku->sku_id_origin     = $storeSkuCode;
            $storeSku->product_id_origin = $storeProductCode;
            $storeSku->product_id        = $sku->product->id;
            $storeSku->save();
        }
    }

    /**
     * @param Store $store
     * @param array $model
     * @param array $item
     * @return Product|object
     */
    protected function createProduct(Store $store, $model, array $item)
    {
        $data = array_merge($this->makeDataProduct($model, $item), [
            'tenant_id' => $this->merchant->tenant_id,
            'creator_id' => $this->creator->id,
            'merchant_id' => $this->merchant->id,
            'status' => Product::STATUS_ON_SELL,
        ]);

        $product = DB::transaction(function () use ($store, $data, $model) {
            $product = Product::create($data);
            $this->createProductMerchant($product);
            $this->createProductSku($product, $model);
            (new ProductCreated($product->id))->queue();

            return $product;
        });
        $this->logger->info('created product ' . $product->code, $data);

        return $product;
    }

    /**
     * @param Product $product
     */
    public function createProductMerchant(Product $product)
    {
        ProductMerchant::create([
            'product_id' => $product->id,
            'merchant_id' => $this->merchant->id
        ]);
    }


    /**
     * @param Product $product
     * @param array $model
     *
     */
    protected function createProductSku(Product $product, $model)
    {
        $currentPrice = Arr::get($model, 'current_price');
        if (!$currentPrice) {
            $currentPrice = Arr::get($model, 'original_price');
        }

        /** @var Sku $sku */
        $sku = $product->skus()->create([
            'tenant_id' => $product->tenant_id,
            'merchant_id' => $product->merchant_id,
            'creator_id' => $product->creator_id,
            'status' => Sku::STATUS_ON_SELL,
            'product_id_origin' => $product->product_id_origin,
            'sku_id_origin' => $product->sku_id_origin,
            'code' => $product->code,
            'name' => $product->name,
            'images' => $product->images,
            'retail_price' => $currentPrice,
            'weight' => $product->weight,
            'height' => $product->height,
            'width' => $product->width,
            'length' => $product->length
        ]);

        $storeSkuCode     = $sku->sku_id_origin;
        $storeProductCode = $sku->product_id_origin;
        $storeSku         = $this->store->storeSkus()
            ->where('tenant_id', $this->merchant->tenant_id)
            ->where('sku_id_origin', $storeSkuCode)
            ->where('sku_id', $storeSkuCode)->first();

        if (!$storeSku instanceof StoreSku) {
            $this->store->storeSkus()->create([
                'tenant_id' => $sku->tenant_id,
                'code' => $storeSkuCode,
                'marketplace_code' => $this->store->marketplace_code,
                'marketplace_store_id' => $this->store->marketplace_store_id,
                'sku_id' => $sku->id,
                'sku_id_origin' => $storeSkuCode,
                'product_id' => $sku->product->id,
                'product_id_origin' => $storeProductCode
            ]);
        } else {
            $storeSku->sku_id_origin     = $storeSkuCode;
            $storeSku->product_id_origin = $storeProductCode;
            $storeSku->product_id        = $sku->product->id;
            $storeSku->save();
        }

        $sku->prices()->updateOrCreate([
            'merchant_id' => $sku->merchant_id,
        ], [
            'retail_price' => $currentPrice
        ]);
    }
}
