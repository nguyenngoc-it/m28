<?php

namespace Modules\Product\Models;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Category\Models\Category;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Models\Order;
use Modules\Tenant\Models\Tenant;

class SkuCombo extends Model
{
    protected $table = 'sku_combos';

    /**
     * Trạng thái
     */
    const STATUS_NEW      = 'NEW'; // Mới, Khởi Tạo
    const STATUS_ACTIVE   = 'ACTIVE';
    const STATUS_INACTIVE = 'INACTIVE';
    const STATUS_ON_SELL = 'ON_SELL'; //Đang bán


    protected $casts = [
        'image' => 'json',
        'snap_sku' => 'json'
    ];

    /**
     * @return BelongsToMany
     */
    public function skus()
    {
        return $this->belongsToMany(Sku::class, 'sku_combo_skus', 'sku_combo_id', 'sku_id')->withTimestamps();
    }

    /**
     * @return HasMany
     */
    public function skuComboSkus()
    {
        return $this->hasMany(SkuComboSku::class, 'sku_combo_id', 'id');
    }

    /**
     * @return BelongsToMany
     */
    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_sku_combos', 'sku_combo_id', 'order_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    /** lọc theo merchant id
     * @param $query
     * @param $merchantId
     * @return mixed
     */
    public function scopeMerchant($query, $merchantId)
    {
        if ($merchantId) {
            return $query->where('sku_combos.merchant_id', $merchantId);
        } else {
            return $query;
        }
    }

    /** lọc theo category id
     * @param $query
     * @param $categoryId
     * @return mixed
     */
    public function scopeCategory($query, $categoryId)
    {
        if ($categoryId) {
            return $query->where('sku_combos.category_id', $categoryId);
        } else {
            return $query;
        }
    }

    /** lọc theo code
     * @param $query
     * @param $skuCode
     * @return mixed
     */
    public function scopeSkuComboCode($query, $skuComboCode)
    {
        if ($skuComboCode) {
            return $query->where('sku_combos.code', $skuComboCode);
        } else {
            return $query;
        }
    }

    /** lọc theo code của sku con
     * @param $query
     * @param $skuCode
     * @return mixed
     */
    public function scopeSkuCode($query, $skuCode)
    {
        if ($skuCode) {
            return $query->join('sku_combo_skus', 'sku_combos.id', '=', 'sku_combo_skus.sku_combo_id')
                         ->join('skus', 'sku_combo_skus.sku_id', '=', 'skus.id')
                         ->where('skus.code', $skuCode);
        } else {
            return $query;
        }
    }

    /**
     * Scope a query to only include users of a given type.
     *
     * @param Builder $query
     * @param mixed $createdAt
     * @return Builder
     */
    public function scopeCreatedAt($query, $createdAt)
    {
        $createdAtFromRaw = data_get($createdAt, 'from');
        $createdAtToRaw   = data_get($createdAt, 'to');

        $createdAtFrom = Carbon::parse($createdAtFromRaw)->startOfDay();
        $createdAtTo   = Carbon::parse($createdAtToRaw)->endOfDay();
        if ($createdAtToRaw && $createdAtToRaw) {
            return $query->whereBetween('sku_combos.created_at', [$createdAtFrom, $createdAtTo]);
        } else {
            return $query;
        }
    }

    /** lọc theo tên sku combo
     * @param $query
     * @param $skuComboName
     * @return mixed
     */
    public function scopeSkuComboName($query, $skuComboName)
    {
        if ($skuComboName){
            return $query->where('sku_combos.name', 'LIKE', "%{$skuComboName}%");
        }else {
            return $query;
        }
    }

    /** lọc theo status sku combo
     * @param $query
     * @param $skuComboStatus
     * @return mixed
     */
    public function scopeSkuComboStatus($query, $skuComboStatus)
    {
        if ($skuComboStatus){
            return $query->where('sku_combos.status', $skuComboStatus);
        }else {
            return $query;
        }
    }

    public function scopeKeyword($query, $keyword)
    {
        if ($keyword) {
            return $query->where(function($query) use ($keyword){
                return $query->where('sku_combos.name', 'LIKE', '%' . $keyword . '%')
                             ->orWhere('sku_combos.code', 'LIKE', '%' . $keyword . '%');
            });
        } else {
            return $query;
        }
    }

}
