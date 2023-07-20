<?php

namespace Modules\Order\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ExpectedTransportingOrderSnapshot
 * @package Modules\Order\Models
 *
 * @property int id
 * @property int order_id
 * @property float weight
 * @property float height
 * @property float width
 * @property float length
 * @property array skus
 * @property array apply_price
 */
class ExpectedTransportingOrderSnapshot extends Model
{
    protected $table = 'expected_transporting_order_snapshots';

    protected $casts = [
        'skus' => 'json',
        'apply_price' => 'json'
    ];

    /**
     * @return BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
