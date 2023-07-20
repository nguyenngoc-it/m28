<?php

namespace Modules\PurchasingPackage\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Sku;
use Modules\PurchasingOrder\Models\PurchasingVariant;

/**
 * Class PurchasingPackageItem
 *
 * @property int id
 * @property int purchasing_package_id
 * @property int sku_id
 * @property int purchasing_variant_id
 * @property int quantity
 * @property int received_quantity
 *
 * @property PurchasingPackage|null purchasingPackage
 * @property PurchasingVariant|null purchasingVariant
 * @property Sku|null sku
 */
class PurchasingPackageItem extends Model
{
    protected $table = 'purchasing_package_items';

    protected $fillable = [
        'purchasing_package_id', 'sku_id', 'purchasing_variant_id', 'quantity', 'received_quantity'
    ];

    /**
     * @return BelongsTo
     */
    public function purchasingPackage()
    {
        return $this->belongsTo(PurchasingPackage::class, 'purchasing_package_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function purchasingVariant()
    {
        return $this->belongsTo(PurchasingVariant::class, 'purchasing_variant_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function sku()
    {
        return $this->belongsTo(Sku::class, 'sku_id', 'id');
    }
}
