<?php

namespace Modules\OrderPacking\Models;

use App\Base\Model;
use App\Traits\ChangeStatusViaWorkflow;
use Carbon\Carbon;
use Gobiz\Workflow\SubjectInterface;
use Gobiz\Workflow\WorkflowInterface;
use Gobiz\Workflow\WorkflowService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentOrderPacking;
use Modules\FreightBill\Models\FreightBill;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Models\Order;
use Modules\OrderExporting\Models\OrderExporting;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;
use Modules\Service\Models\Service as ServiceModel;
use Modules\Service\Models\ServicePrice;

/**
 * Class OrderPacking
 * @package Modules\OrderPacking\Models
 *
 * @property int id
 * @property int shipping_partner_id
 * @property int tenant_id
 * @property int merchant_id
 * @property int order_id
 * @property int freight_bill_id
 * @property int warehouse_id
 * @property int total_quantity
 * @property int total_quantity_packaged
 * @property float total_values
 * @property float width
 * @property float height
 * @property float length
 * @property string payment_type
 * @property string payment_method
 * @property string status
 * @property string remark
 * @property Carbon intended_delivery_at
 * @property string error_type
 * @property int packing_type_id
 * @property float service_amount
 * @property int picker_id
 * @property int picking_session_id
 * @property int pickup_truck_id
 * @property boolean priority
 *
 * @property Collection|OrderPackingItem[] orderPackingItems
 * @property Order order
 * @property Warehouse|null warehouse
 * @property Merchant merchant
 * @property ShippingPartner shippingPartner
 * @property FreightBill|null freightBill
 * @property Collection freightBills
 * @property OrderExporting|null orderExporting
 * @property User|null picker
 * @property Collection|Document[] $documents
 * @property PackingType packingType
 * @property OrderPackingService[]|Collection orderPackingServices
 * @property ServiceModel[]|Collection services
 * @property Collection servicePrices
 * @property Carbon|null grant_picker_at
 *
 */
class OrderPacking extends Model implements SubjectInterface
{
    use ChangeStatusViaWorkflow;

    const STATUS_WAITING_PROCESSING = 'WAITING_PROCESSING'; // Chờ xử lý
    const STATUS_WAITING_PICKING    = 'WAITING_PICKING'; // Chờ nhặt hàng
    const STATUS_WAITING_PACKING    = 'WAITING_PACKING'; // Chờ đóng gói
    const STATUS_PACKED             = 'PACKED'; // Đã đóng gói
    const STATUS_CANCELED           = 'CANCELED'; // Huỷ bỏ

    const SCAN_TYPE_ORDER        = 'ORDER'; // Quét theo mã đơn
    const SCAN_TYPE_FREIGHT_BILL = 'FREIGHT_BILL'; // Quét theo mã vận đơn

    const ERROR_TYPE_NO_WEIGHT                 = 'NO_WEIGHT'; // Không có cân nặng
    const ERROR_TYPE_ODZ                       = 'ODZ'; // Ngoài khu vực giao hàng
    const ERROR_TYPE_TECHNICAL                 = 'TECHNICAL'; // Lỗi kỹ thuật
    const ERROR_TYPE_SHIPPING_PARTNER_INACTIVE = 'SHIPPING_PARTNER_INACTIVE'; // Đơn vị vận chuyển bị dừng hoạt động
    const ERROR_RECEIVER_POSTAL_CODE           = 'NO_RECEIVER_POSTAL_CODE'; // không có mã bưu chính

    public static $listStatus = [
        self::STATUS_WAITING_PROCESSING,
        self::STATUS_WAITING_PICKING,
        self::STATUS_WAITING_PACKING,
        self::STATUS_PACKED,
        self::STATUS_CANCELED
    ];

    protected $casts = [
        'total_values' => 'double',
        'intended_delivery_at' => 'datetime',
        'grant_picker_at' => 'datetime',
    ];

    /**
     * @return HasMany
     */
    public function orderPackingItems()
    {
        return $this->hasMany(OrderPackingItem::class);
    }

    /**
     * @return HasMany
     */
    public function orderPackingServices()
    {
        return $this->hasMany(OrderPackingService::class, 'order_packing_id', 'id');
    }


    /**
     * @return BelongsToMany
     */
    public function services()
    {
        return $this->belongsToMany(ServiceModel::class, 'order_packing_services', 'order_packing_id', 'service_id');
    }

    /**
     * @return BelongsToMany
     */
    public function servicePrices(): BelongsToMany
    {
        return $this->belongsToMany(ServicePrice::class, 'order_packing_services', 'order_packing_id', 'service_price_id');
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
     * @return HasMany
     */
    public function freightBills()
    {
        return $this->hasMany(FreightBill::class);
    }

    /**
     * @return BelongsTo
     */
    public function packingType()
    {
        return $this->belongsTo(PackingType::class);
    }

    /**
     * @return HasOne
     */
    public function orderExporting()
    {
        return $this->hasOne(OrderExporting::class);
    }

    /**
     * @return belongsTo
     */
    public function document()
    {
        return $this->belongsTo(DocumentOrderPacking::class);
    }

    /**
     * @return BelongsToMany
     */
    public function documents()
    {
        return $this->belongsToMany(Document::class, 'document_order_packings', 'order_packing_id', 'document_id');
    }

    /**
     * @return bool
     */
    public function canChangeShippingPartner()
    {
        return (in_array($this->getAttribute('status'), [self::STATUS_WAITING_PROCESSING]));
    }

    /**
     * @return bool
     */
    public function canCreateTrackingNo()
    {
        return (in_array($this->getAttribute('status'), [self::STATUS_WAITING_PROCESSING]));
    }

    /**
     * @return bool
     */
    public function canCancelTrackingNo()
    {
        $status = $this->getAttribute('status');
        return (in_array($status, [OrderPacking::STATUS_WAITING_PICKING, OrderPacking::STATUS_WAITING_PACKING]));
    }

    /**
     * @param FreightBill $freightBill
     * @return OrderPacking
     */
    public function updateFreightBill(FreightBill $freightBill)
    {
        return Service::orderPacking()->updateFreightBill($this, $freightBill);
    }

    /**
     * @return BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return WorkflowInterface
     */
    public function getWorkflow()
    {
        return WorkflowService::workflow('order_packing');
    }

    /**
     * @return BelongsTo
     */
    public function picker()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Lây tổng trọng lượng của các skus
     *
     * @return float
     */
    public function getTotalWeight()
    {
        /**
         * @var OrderPackingItem $orderPackingItem
         */
        $weight = 0;
        foreach ($this->getAttribute('orderPackingItems') as $orderPackingItem) {
            $weight += $orderPackingItem->sku->weight * $orderPackingItem->quantity;
        }

        return $weight;
    }
}
