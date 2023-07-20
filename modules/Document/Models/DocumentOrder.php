<?php

namespace Modules\Document\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Order\Models\Order;

/**
 * Class DocumentOrder
 *
 * @property int $document_id
 * @property int $order_id
 *
 * @property Document document
 */
class DocumentOrder extends Model
{
    protected $table = 'document_orders';

    /**
     * @return BelongsTo
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * @return BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
