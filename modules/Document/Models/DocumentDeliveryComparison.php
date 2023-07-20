<?php

namespace Modules\Document\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Models\Order;

/**
 * Class DocumentFreightBillInventory
 * @package Modules\Document\Models
 *
 * @property int id
 * @property int document_id
 * @property int freight_bill_id
 * @property int order_id
 * @property string freight_bill_code
 * @property int skus_count_order
 * @property int skus_count_carrier
 * @property float cod_total_order
 * @property float cod_total_carrier
 * @property float weight_total_order
 * @property float weight_total_carrier
 * @property array errors
 * @property string status
 *
 * @property Document document
 * @property Order order
 * @property FreightBill freightBill
 */
class DocumentDeliveryComparison extends Model
{
    protected $table = 'document_delivery_comparisons';

    CONST STATUS_CORRECT   = "correct";
    CONST STATUS_INCORRECT = "incorrect";

    protected $casts = [
        'cod_total_order' => 'float',
        'cod_total_carrier' => 'float',
        'weight_total_order' => 'float',
        'weight_total_carrier' => 'float',
        'errors' => 'array'
    ];

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

    /**
     * @return BelongsTo
     */
    public function freightBill()
    {
        return $this->belongsTo(FreightBill::class);
    }
}
