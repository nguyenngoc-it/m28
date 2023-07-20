<?php

namespace Modules\Document\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\OrderExporting\Models\OrderExporting;
use Modules\OrderPacking\Models\OrderPacking;

/**
 * Class DocumentOrderPacking
 *
 * @property int $document_id
 * @property int $order_packing_id
 *
 * @property Document document
 */
class DocumentOrderPacking extends Model
{
    protected $table = 'document_order_packings';


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
    public function orderPacking()
    {
        return $this->belongsTo(OrderPacking::class);
    }
}
