<?php

namespace Modules\OrderExporting\Models;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Document\Models\Document;
use Modules\FreightBill\Models\FreightBill;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Models\Order;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Product\Models\Sku;
use Modules\ShippingPartner\Models\ShippingPartner;

/**
 * Class OrderExporting
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $warehouse_id
 * @property int $order_id
 * @property int $shipping_partner_id
 * @property int $freight_bill_id
 * @property int $order_packing_id
 * @property int $creator_id
 * @property int $handler_id
 * @property string $receiver_name
 * @property string $receiver_phone
 * @property string $receiver_address
 * @property float $total_quantity
 * @property float $total_value
 * @property string $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Collection|Sku[] $skus
 * @property Collection orderExportingItems
 * @property Order order
 * @property Merchant merchant
 * @property ShippingPartner shippingPartner
 * @property FreightBill|null freightBill
 * @property OrderPacking|null orderPacking
 */
class OrderExporting extends Model
{
    const STATUS_NEW      = 'new'; // Chờ xử lý
    const STATUS_PROCESS  = 'process'; // Đang xử lý (Nằm trong chứng từ xuất hàng nháp)
    const STATUS_FINISHED = 'finished'; // Đã xử lý (Nằm trong chứng từ xuất hàng đã ký)

    protected $table = 'order_exportings';

    protected $casts = [
        'total_quantity' => 'float',
        'total_value' => 'float',
    ];

    /**
     * @return BelongsToMany
     */
    public function skus()
    {
        return $this->belongsToMany(Sku::class, 'order_packing_items', 'order_exporting_id', 'sku_id');
    }

    /**
     * @return HasMany
     */
    public function orderExportingItems()
    {
        return $this->hasMany(OrderExportingItem::class);
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
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * @return BelongsTo
     */
    public function shippingPartner()
    {
        return $this->belongsTo(ShippingPartner::class);
    }

    /**
     * @return BelongsTo
     */
    public function freightBill()
    {
        return $this->belongsTo(FreightBill::class);
    }

    /**
     * @return BelongsTo
     */
    public function orderPacking()
    {
        return $this->belongsTo(OrderPacking::class);
    }

    /**
     * @return Document|null
     */
    public function documentExporting()
    {
        return $this->belongsToMany(Document::class, 'document_order_exportings')->where('status', '<>', Document::STATUS_CANCELLED)->first();
    }
}
