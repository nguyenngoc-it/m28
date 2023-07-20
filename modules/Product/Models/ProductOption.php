<?php

namespace Modules\Product\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class ProductOption
 *
 * @property int $id
 * @property int $product_id
 * @property string $label
 *
 * @property Product|null $product
 * @property ProductOptionValue[]|Collection $options
 */
class ProductOption extends Model
{
    protected $table = 'product_options';

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