<?php

namespace Modules\Product\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Merchant\Models\Merchant;

/**
 * Class ProductOption
 *
 * @property int $id
 * @property int $product_id
 * @property int $merchant_id
 *
 * @property Product|null $product
 * @property Merchant|null $merchant
 */
class ProductMerchant extends Model
{
    protected $table = 'product_merchants';

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
    public function merchant()
    {
        return $this->hasMany(Merchant::class, 'merchant_id', 'id');
    }

}
