<?php

namespace Modules\Product\Transformers;


use League\Fractal\TransformerAbstract;
use Modules\Category\Models\Category;
use Modules\Category\Transformers\CategoryTransformerNew;
use Modules\Product\Models\Sku;
use Modules\Stock\Transformers\StockTransformer;
use Modules\Warehouse\Transformers\WarehouseTransformerNew;
use Modules\WarehouseStock\Transformers\WarehouseStockTransformer;

class SkuTransformer extends TransformerAbstract
{
    protected $request;

    public function __construct()
    {
        $this->setDefaultIncludes(['warehouse_stocks', 'category']);
        $this->setAvailableIncludes(['sku_childrens', 'stocks']);
        $this->request = request()->all();
    }

    public function transform(Sku $sku)
    {
        $dropShip = ($sku->product ? $sku->product->dropship : 0);
        return [
            'id' => (int)$sku->id,
            'product_id' => (int)$sku->product_id,
            'name' => $sku->name,
            'code' => $sku->code,
            'weight' => $sku->weight,
            'height' => $sku->height,
            'width' => $sku->width,
            'length' => $sku->length,
            'images' => ($sku->product ? $sku->product->images : ''),
            'status' => $sku->status,
            'dropship' => $dropShip,
            'price' => $sku->retail_price,
            'safety_stock' => $sku->safety_stock
        ];
    }

    /**
     * Include WareHouse
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function includeWarehouseStocks(Sku $sku)
    {
        $warehouseStocks = $sku->warehouseStocks;

        return $this->collection($warehouseStocks, new WarehouseStockTransformer);
    }

    /**
     * Include WareHouse
     *
     * @return \League\Fractal\Resource\Collection
     */
    public function includeStocks(Sku $sku)
    {
        $stocks = $sku->stocks;

        $warehouseId     = data_get($this->request, 'warehouse_id');
        $warehouseAreaId = data_get($this->request, 'warehouse_area_id');
        $outOfStock      = data_get($this->request, 'out_of_stock');

        $stocksFiltered = $stocks;
        if ($warehouseId) {
            $stocksFiltered = $stocks->filter(function ($value, $key)  use ($warehouseId){
                if ($value->warehouse_id == $warehouseId) {
                    return $value;
                }
            });
        }

        if ($warehouseAreaId) {
            $stocksFiltered = $stocksFiltered->filter(function ($value, $key)  use ($warehouseAreaId){
                if ($value->warehouse_area_id == $warehouseAreaId) {
                    return $value;
                }
            });
        }

        if (!is_null($outOfStock)) {
            $stocksFiltered = $stocksFiltered->filter(function ($value, $key)  use ($outOfStock){
                if ($outOfStock && $value->quantity == 0 && $value->real_quantity == 0) {
                    return $value;
                }

                if (!$outOfStock && $value->quantity > 0 && $value->real_quantity > 0) {
                    return $value;
                }
            });
        }

        if ($stocksFiltered) {
            return $this->collection($stocksFiltered, new StockTransformer);
        } else {
            return $this->null();
        }
    }

    /**
     * Include category
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeCategory(Sku $sku)
    {
        $category = $sku->category;
        if (!$category) {
            $category = new Category();
        }

        return $this->item($category, new CategoryTransformerNew);
    }

    /**
     * Include category
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeSkuChildrens(Sku $sku)
    {
        $skus = $sku->skuChildren;

        if ($skus) {
            return $this->collection($skus, new SkuTransformer);
        } else {
            return $this->null();
        }
    }
}
