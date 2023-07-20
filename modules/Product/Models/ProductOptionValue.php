<?php

namespace Modules\Product\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class ProductOptionValue
 *
 * @property int $id
 * @property int $product_id
 * @property int $product_option_id
 * @property string $label
 *
 * @property Product|null $product
 * @property ProductOptionValue[]|Collection $productOption $options
 */
class ProductOptionValue extends Model
{
    protected $table = 'product_option_values';

    /**
     * @return BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function options()
    {
        return $this->hasMany(ProductOptionValue::class, 'product_option_id', 'id');
    }
}