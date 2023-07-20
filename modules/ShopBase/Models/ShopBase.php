<?php

namespace Modules\ShopBase\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Merchant\Models\Merchant;

/**
 * Class ShopBase
 *
 * @property int $id
 * @property int $merchant_id
 * @property int $order_id
 * @property boolean $status
 * @property string $data
 * @property string $errors
 *
 * @property Merchant|null $merchant
 */
class ShopBase extends Model
{
    protected $table = 'shop_bases';

    const TOPIC_ORDER_CREATE = "orders/create";

    /**
     * @return BelongsTo
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id', 'id');
    }
}