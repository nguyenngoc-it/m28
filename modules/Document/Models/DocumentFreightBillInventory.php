<?php

namespace Modules\Document\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Models\Order;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Transaction\Services\MerchantTransObjInterface;

/**
 * Class DocumentFreightBillInventory
 * @package Modules\Document\Models
 *
 * @property int id
 * @property int document_id
 * @property int freight_bill_id
 * @property int order_packing_id
 * @property int order_id
 * @property string freight_bill_code
 * @property float cod_total_amount
 * @property float cod_paid_amount
 * @property float cod_fee_amount
 * @property float shipping_amount
 * @property float extent_amount
 * @property float other_fee
 * @property boolean warning
 * @property string status
 * @property string finance_status_cod
 * @property string finance_status_fee
 * @property string finance_status_extent
 *
 * @property-read  float total_amount
 *
 * @property Document document
 * @property Order order
 * @property OrderPacking orderPacking
 * @property FreightBill freightBill
 */
class DocumentFreightBillInventory extends Model implements MerchantTransObjInterface
{
    protected $table = 'document_freight_bill_inventories';

    protected $fillable = ['document_id', 'freight_bill_id', 'order_packing_id', 'order_id', 'freight_bill_code',
        'cod_total_amount', 'cod_paid_amount', 'cod_fee_amount', 'shipping_amount', 'extent_amount', 'warning', 'other_fee', 'status',
        'finance_status_cod', 'finance_status_fee', 'finance_status_extent'
    ];

    /**
     * Trang thái tài chính
     */
    const FINANCE_STATUS_UNPAID = 'UNPAID'; // Chưa thanh toán
    const FINANCE_STATUS_PAID   = 'PAID'; // Đã thanh toán

    const STATUS_CORRECT   = "correct";
    const STATUS_INCORRECT = "incorrect";

    public function getObjectType()
    {
        return 'DOCUMENT_FREIGHT_BILL_INVENTORY';
    }

    public function getObjectId()
    {
        return $this->getKey();
    }

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
    public function orderPacking()
    {
        return $this->belongsTo(OrderPacking::class);
    }

    /**
     * @return BelongsTo
     */
    public function freightBill()
    {
        return $this->belongsTo(FreightBill::class);
    }

    /**
     * @return float
     */
    public function getTotalAmount()
    {
        return $this->getAttribute('cod_paid_amount') - $this->getAttribute('cod_fee_amount') - $this->getAttribute('shipping_amount');
    }
}
