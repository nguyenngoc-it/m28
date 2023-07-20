<?php

namespace Modules\Product\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Merchant\Models\Merchant;

/**
 * Class SkuPrice
 *
 * @property int $id
 * @property int $merchant_id
 * @property int $sku_id
 * @property double $cost_price
 * @property double $wholesale_price
 * @property double $retail_price
 *
 * @property Merchant|null merchant
 * @property Sku|null sku
 */
class SkuPrice extends Model
{

    protected $table = 'sku_prices';

    /**
     * @return BelongsTo
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function sku()
    {
        return $this->belongsTo(Sku::class, 'sku_id', 'id');
    }
}
