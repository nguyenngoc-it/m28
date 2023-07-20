<?php

namespace Modules\Product\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class SkuOptionValue
 *
 * @property int $id
 * @property int $sku_id
 * @property int $product_option_value_id
 *
 * @property Sku|null $sku
 * @property ProductOption[]|Collection $options
 * @property ProductOptionValue[]|Collection $values
 */
class SkuOptionValue extends Model
{
    protected $table = 'sku_option_values';

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
    public function options()
    {
        return $this->hasMany(ProductOption::class, 'product_option_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function values()
    {
        return $this->hasMany(ProductOptionValue::class, 'product_option_value_id', 'id');
    }
}
