<?php

namespace Modules\Document\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Supplier\Models\Supplier;

/**
 * Class DocumentFreightBillInventory
 * @package Modules\Document\Models
 *
 * @property int id
 * @property int document_id
 * @property int supplier_id
 * @property string transaction_code
 * @property string payment_time
 * @property string note
 * @property float amount
 * @property string action
 *
 * @property Document document
 * @property Supplier $supplier
 */
class DocumentSupplierTransaction extends Model
{
    protected $table = 'document_supplier_transactions';

    protected $fillable = ['document_id', 'supplier_id', 'amount', 'transaction_code', 'payment_time', 'note', 'action'];

    protected $casts = [
        'amount' => 'float',
        'payment_time' => 'datetime',
    ];

    const ACTION_DEPOSIT  = 'DEPOSIT'; // nap tien
    const ACTION_COLLECT  = 'COLLECT'; //chi -  tiền ví seller

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
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
