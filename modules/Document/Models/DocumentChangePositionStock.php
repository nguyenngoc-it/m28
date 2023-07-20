<?php

namespace Modules\Document\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Stock\Models\Stock;

/**
 * Class DocumentSkuImporting
 *
 * @property int id
 * @property int document_id
 * @property int stock_id_from
 * @property int stock_id_to
 * @property int creator_id
 *
 * @property Stock|null stockFrom
 * @property Stock|null stockTo
 *
 */
class DocumentChangePositionStock extends Model
{
    protected $table = 'document_change_position_stocks';

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
    public function stockFrom()
    {
        return $this->belongsTo(Stock::class, 'stock_id_from');
    }

    /**
     * @return BelongsTo
     */
    public function stockTo()
    {
        return $this->belongsTo(Stock::class, 'stock_id_to');
    }

}
