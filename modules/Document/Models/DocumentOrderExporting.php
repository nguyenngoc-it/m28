<?php

namespace Modules\Document\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\OrderExporting\Models\OrderExporting;

/**
 * Class DocumentOrderExporting
 *
 * @property int $document_id
 * @property int $order_exporting_id
 *
 * @property Document document
 */
class DocumentOrderExporting extends Model
{
    protected $table = 'document_order_exportings';

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
    public function orderExporting()
    {
        return $this->belongsTo(OrderExporting::class);
    }
}
