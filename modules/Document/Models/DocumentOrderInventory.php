<?php

namespace Modules\Document\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\OrderExporting\Models\OrderExporting;

/**
 * Class Document
 *
 * @property int $id
 * @property int document_id
 * @property int document_exporting_id
 * @property int order_exporting_id
 * @property string barcode
 * @property boolean checked
 *
 * @property OrderExporting orderExporting
 */
class DocumentOrderInventory extends Model
{

    protected $casts = [
        'checked' => 'boolean'
    ];

    /**
     * @return BelongsTo
     */
    public function orderExporting()
    {
        return $this->belongsTo(OrderExporting::class);
    }

}
