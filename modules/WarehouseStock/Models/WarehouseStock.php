<?php

namespace Modules\WarehouseStock\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\Warehouse\Models\Warehouse;

/**
 * Class WarehouseStock
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $product_id
 * @property int $sku_id
 * @property int $warehouse_id
 * @property int $quantity
 * @property int $real_quantity
 * @property int $purchasing_quantity
 * @property int $packing_quantity
 * @property int $saleable_quantity
 * @property int $min_quantity
 * @property boolean out_of_stock
 * @property string sku_status
 *
 *
 * @property Product $product
 * @property Sku $sku
 * @property Warehouse $warehouse
 */
class WarehouseStock extends Model
{
    protected $table = 'warehouse_stocks';

    protected $casts = [
        'out_of_stock' => 'boolean'
    ];

    /**
     * @return BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function sku()
    {
        return $this->belongsTo(Sku::class, 'sku_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }
}
