<?php

namespace Modules\PurchasingOrder\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Collection;
use Modules\Product\Models\Sku;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\PurchasingPackage\Models\PurchasingPackageItem;

/**
 * Class PurchasingVariant
 *
 * @property int id
 * @property int tenant_id
 * @property int marketplace
 * @property string variant_id
 * @property integer sku_id
 * @property string code
 * @property string name
 * @property string translated_name
 * @property string image
 * @property string properties
 * @property string product_url
 * @property string product_image
 * @property string supplier_code
 * @property string supplier_name
 * @property string supplier_url
 * @property string spec_id
 * @property string payload
 *
 * @property Sku|null sku
 * @property PurchasingOrderItem[]|Collection purchasingOrderItems
 * @property PurchasingPackageItem[]|Collection purchasingPackageItems
 */
class PurchasingVariant extends Model
{
    protected $table = 'purchasing_variants';

    protected $casts = [
        'properties' => 'array',
        'payload' => 'json',
    ];

    /**
     * @return BelongsTo
     */
    public function sku()
    {
        return $this->belongsTo(Sku::class, 'sku_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function purchasingOrderItems()
    {
        return $this->hasMany(PurchasingOrderItem::class, 'purchasing_variant_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function purchasingPackageItems()
    {
        return $this->hasMany(PurchasingPackageItem::class, 'purchasing_variant_id', 'id');
    }
}
