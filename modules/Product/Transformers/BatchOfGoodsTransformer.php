<?php
namespace Modules\Product\Transformers;


use League\Fractal\TransformerAbstract;
use Modules\Merchant\Transformers\MerchantTransformer;
use Modules\Product\Models\BatchOfGood;
use Modules\Product\Models\Sku;

class BatchOfGoodsTransformer extends TransformerAbstract
{

    protected $request;

    protected $skuChildBySkus = null;
	
	public function __construct()
	{
		$this->setAvailableIncludes(['sku', 'sku_child', 'sku_child_by_sku', 'merchants']);
		$this->setDefaultIncludes([]);
        $this->request = request()->all();
	}
	
	public function transform(BatchOfGood $batchOfGood)
	{
        $this->includeSkuChildBySku($batchOfGood);
        
        $totalRealQuantity = 0;
        $totalQuantity     = 0;
        $totalStorageFee   = 0;
        if ($this->skuChildBySkus) {
            foreach ($this->skuChildBySkus as $dataSkuChild) {
                $stocks = $dataSkuChild->stocks;

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
                    foreach ($stocksFiltered as $stock) {
                        $totalRealQuantity += $stock->real_quantity;
                        $totalQuantity     += $stock->quantity;
                        $totalStorageFee   += $stock->total_storage_fee;
                    }
                }
            }
        }

        $this->skuChildBySkus = null;

	    return [
	        'id'                  => (int) $batchOfGood->id,
	        'code'                => $batchOfGood->code,
	        'total_quantity'      => $totalQuantity,
	        'total_real_quantity' => $totalRealQuantity,
	        'total_storage_fee'   => $totalStorageFee,
	        'cost_of_goods'       => $batchOfGood->cost_of_goods,
	        'production_at'       => $batchOfGood->production_at,
	        'expiration_at'       => $batchOfGood->expiration_at,
	        'created_at'          => $batchOfGood->created_at,
	        'updated_at'          => $batchOfGood->updated_at,
	    ];
	}

	/**
     * Include Creator
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeSku(BatchOfGood $batchOfGood)
    {
        $sku = $batchOfGood->sku;
		if ($sku) {
			return $this->item($sku, new SkuTransformer);
		} else {
            return $this->null();
        }
    }

    /**
     * Include Creator
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeSkuChild(BatchOfGood $batchOfGood)
    {
        $sku = $batchOfGood->skuChild;
		if ($sku) {
			return $this->item($sku, new SkuTransformer);
		} else {
            return $this->null();
        }
    }

    /**
     * Include Creator
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeSkuChildBySku(BatchOfGood $batchOfGood)
    {

        if (!$this->skuChildBySkus) {
            $skuParentId = $batchOfGood->sku_id;

            $skuId           = data_get($this->request, 'sku_id', 0);
            $skuCode         = data_get($this->request, 'sku_code');
            $skuName         = data_get($this->request, 'sku_name');
            $merchantId      = data_get($this->request, 'merchant_id');
            $warehouseId     = data_get($this->request, 'warehouse_id');
            $warehouseAreaId = data_get($this->request, 'warehouse_area_id');
            $outOfStock      = data_get($this->request, 'out_of_stock');

            $skuChildList = BatchOfGood::where('batch_of_goods.sku_id', $skuParentId)
                                        ->skuId($skuId)
                                        ->skuCode($skuCode)
                                        ->skuName($skuName)
                                        ->merchantId($merchantId)
                                        ->warehouseId($warehouseId)
                                        ->warehouseAreaId($warehouseAreaId)
                                        ->outOfStock($outOfStock)
                                        ->get();

            $skuChildListId = [];
            if ($skuChildList) {
                foreach ($skuChildList as $skuChild) {
                    $skuChildListId[] = $skuChild->sku_child_id;
                }
            }

            $this->skuChildBySkus = Sku::whereIn('id', $skuChildListId)->get();
        }

		if ($this->skuChildBySkus) {
			return $this->collection( $this->skuChildBySkus, new SkuTransformer);
		} else {
            return $this->null();
        }
    }

    public function includeMerchants(BatchOfGood $batchOfGood)
    {
        $this->includeSkuChildBySku($batchOfGood);

        $merchants = [];

        if ($this->skuChildBySkus) {
            foreach ($this->skuChildBySkus as $skuChildBySku) {
                $merchant = $skuChildBySku->product->merchants;
                foreach ($skuChildBySku->product->merchants as $merchant) {
                    $merchants[$merchant->id] = $merchant;
                }
            }
        }

        $this->skuChildBySkus = null;

		if ($merchants) {
			return $this->collection(collect($merchants), new MerchantTransformer);
		} else {
            return $this->null();
        }
    }

}