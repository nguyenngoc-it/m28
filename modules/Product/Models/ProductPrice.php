<?php

namespace Modules\Product\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\User\Models\User;

/**
 * Class ProductPrice
 *
 * @property int $id
 * @property int creator_id
 * @property int $product_id
 * @property int $tenant_id
 * @property string status
 * @property string type
 *
 * @property Product|null $product
 * @property User|null creator
 * @property ProductPriceDetail[]|Collection priceDetails
 */
class ProductPrice extends Model
{
    protected $table = 'product_prices';

    const STATUS_WAITING_CONFIRM = 'WAITING_CONFIRM';
    const STATUS_ACTIVE          = 'ACTIVE';
    const STATUS_CANCELED        = 'CANCELED';

    const TYPE_COMBO = 'COMBO';
    const TYPE_SKU   = 'SKU';

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
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function priceDetails()
    {
        return $this->hasMany(ProductPriceDetail::class, 'product_price_id', 'id');
    }

    /**
     * @param $combo
     * @return \Illuminate\Database\Eloquent\Model|HasMany|null
     */
    public function priceDetailCombo($combo)
    {
        return $this->priceDetails()->firstWhere('combo', $combo);
    }
}
