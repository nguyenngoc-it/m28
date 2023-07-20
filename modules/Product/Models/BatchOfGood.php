<?php

namespace Modules\Product\Models;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int id
 * @property int sku_id
 * @property int sku_child_id
 * @property string code
 * @property double cost_of_goods
 * @property Carbon production_at
 * @property Carbon expiration_at
 * @property Carbon created_at
 *
 * @property Sku sku
 * @property Sku skuChild
 */
class BatchOfGood extends Model
{
    const LOGIC_FIFO = 'FIFO';
    const LOGIC_LIFO = 'LIFO';
    const LOGIC_FEFO = 'FEFO';

    protected $table = 'batch_of_goods';

    protected $casts = [
        'production_at' => 'datetime',
        'expiration_at' => 'datetime',
    ];

    protected $joinSkuTable = false;
    protected $joinSkuParentTable = false;
    protected $joinStockTable = false;

    /**
     * @return BelongsTo
     */
    public function sku()
    {
        return $this->belongsTo(Sku::class);
    }

    /**
     * @return BelongsTo
     */
    public function skuChild()
    {
        return $this->belongsTo(Sku::class, 'sku_child_id');
    }

    public function scopeSkuId($query, $skuId)
    {
        if ($skuId) {
            return $query->where('batch_of_goods.sku_id', $skuId);
        } else {
            return $query;
        }
    }

    public function scopeSkuCode($query, $skuCode)
    {
        if ($skuCode) {
            if (!$this->joinSkuParentTable) {
                $query = $query->join('skus as skusP', 'batch_of_goods.sku_id', 'skusP.id');
            }
            $query = $query->where('skusP.code', $skuCode);
            $this->joinSkuParentTable = true;

            return $query;
        } else {
            return $query;
        }
    }

    public function scopeSkuName($query, $skuName)
    {
        if ($skuName) {
            if (!$this->joinSkuParentTable) {
                $query = $query->join('skus  as skusP', 'batch_of_goods.sku_id', 'skusP.id');
            }
            $query = $query->where('skusP.name', 'LIKE', '%' . $skuName . '%');
            $this->joinSkuParentTable = true;
            
            return $query;
        } else {
            return $query;
        }
    }

    public function scopeMerchantId($query, $merchantId)
    {
        if ($merchantId) {
            if (!$this->joinSkuTable) {
                $query = $query->join('skus as skusC', 'batch_of_goods.sku_child_id', 'skusC.id');
            }
            $query = $query->leftJoin('product_merchants', 'product_merchants.product_id', 'skusC.product_id')
                            ->where(function($query) use ($merchantId){
                            $query->where('product_merchants.merchant_id', $merchantId)
                                ->orWhere('skusC.merchant_id', $merchantId);
                            return $query;
            });
            $this->joinSkuTable = true;
            
            return $query;
        } else {
            return $query;
        }
    }

    public function scopeWarehouseId($query, $warehouseId)
    {
        if ($warehouseId) {
            if (!$this->joinSkuTable) {
                $query = $query->join('skus as skusC', 'batch_of_goods.sku_child_id', 'skusC.id');
                $this->joinSkuTable = true;
            }
            if (!$this->joinStockTable) {
                $query = $query->join('stocks as stocksC', 'stocksC.sku_id', 'skusC.id');
                $this->joinStockTable = true;
            }
            $query = $query->where('stocksC.warehouse_id', $warehouseId);
            
            return $query;
        } else {
            return $query;
        }
    }

    public function scopeWarehouseAreaId($query, $warehouseAreaId)
    {
        if ($warehouseAreaId) {
            if (!$this->joinSkuTable) {
                $query = $query->join('skus as skusC', 'batch_of_goods.sku_child_id', 'skusC.id');
                $this->joinSkuTable = true;
            }
            if (!$this->joinStockTable) {
                $query = $query->join('stocks as stocksC', 'stocksC.sku_id', 'skusC.id');
                $this->joinStockTable = true;
            }
            $query = $query->where('stocksC.warehouse_area_id', $warehouseAreaId);
            
            return $query;
        } else {
            return $query;
        }
    }

    public function scopeOutOfStock($query, $outOfStock)
    {
        if (!is_null($outOfStock)) {
            if (!$this->joinSkuTable) {
                $query = $query->join('skus as skusC', 'batch_of_goods.sku_child_id', 'skusC.id');
                $this->joinSkuTable = true;
            }
            if (!$this->joinStockTable) {
                $query = $query->join('stocks as stocksC', 'stocksC.sku_id', 'skusC.id');
                $this->joinStockTable = true;
            }
            if ($outOfStock) {
                $query = $query->where('stocksC.quantity', 0)
                               ->where('stocksC.real_quantity', 0);
            } else {
                $query = $query->where('stocksC.quantity', '>', 0)
                               ->where('stocksC.real_quantity', '>', 0);
            }
            
            return $query;
        } else {
            return $query;
        }
    }

}
