<?php

namespace Modules\PurchasingOrder\Models;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\PurchasingManager\Models\PurchasingAccount;
use Modules\PurchasingManager\Models\PurchasingService;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\Service\Models\Service;
use Modules\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Warehouse\Models\Warehouse;

/**
 * Class PurchasingOrder
 *
 * @property int id
 * @property int tenant_id
 * @property int purchasing_service_id
 * @property int purchasing_account_id
 * @property int merchant_id
 * @property string code
 * @property string m1_order_url
 * @property string status
 * @property string marketplace
 * @property string supplier_code
 * @property string supplier_name
 * @property string supplier_url
 * @property string customer_username
 * @property string customer_name
 * @property string customer_phone
 * @property string customer_address
 * @property string receiver_name
 * @property string receiver_phone
 * @property string receiver_country_code
 * @property string receiver_city_code
 * @property string receiver_district_code
 * @property string receiver_ward_code
 * @property string receiver_address
 * @property string receiver_note
 * @property integer ordered_quantity
 * @property integer purchased_quantity
 * @property integer received_quantity
 * @property float exchange_rate
 * @property float original_total_value
 * @property float total_value
 * @property float total_fee
 * @property float grand_total
 * @property float total_paid
 * @property float total_unpaid
 * @property integer warehouse_id
 * @property bool is_putaway
 *
 * @property Carbon ordered_at
 *
 * @property Tenant|null tenant
 * @property PurchasingService|null purchasingService
 * @property PurchasingAccount|null purchasingAccount
 * @property Collection purchasingOrderItems
 * @property Collection purchasingPackages
 * @property Collection purchasingVariants
 * @property Collection services
 * @property Collection purchasingOrderServices
 *
 * @property array permission_views
 */
class PurchasingOrder extends Model
{
    const PERMISSION_VIEW_MAPPING_SKU = 'mapping_sku'; // được phép mapping sku cho sp của đơn

    const STATUS_DEPOSITED           = 'DEPOSITED'; // Đã Đặt Cọc
    const STATUS_PENDING             = 'PENDING'; // Chờ Duyệt
    const STATUS_PURCHASING          = 'PURCHASING'; // Đang mua
    const STATUS_WAITING_FOR_PAYMENT = 'WAITING_FOR_PAYMENT'; // Chờ thanh toán
    const STATUS_PURCHASED           = 'PURCHASED'; // Đã mua
    const STATUS_ACCEPTED            = 'ACCEPTED'; // Chưa mua
    const STATUS_MERCHANT_DELIVERING = 'MERCHANT_DELIVERING'; // Người bán giao
    const STATUS_PUTAWAY             = 'PUTAWAY'; // Hàng về kho
    const STATUS_TRANSPORTING        = 'TRANSPORTING'; // Vận chuyển quốc tế
    const STATUS_READY_FOR_DELIVERY  = 'READY_FOR_DELIVERY'; // Chờ giao
    const STATUS_DELIVERING          = 'DELIVERING'; // Đang giao
    const STATUS_DELIVERED           = 'DELIVERED'; // Khách đã nhận
    const STATUS_CANCELLED           = 'CANCELLED'; // Hủy bỏ
    const STATUS_OUT_OF_STOCK        = 'OUT_OF_STOCK'; // Hết hàng
    const STATUS_MIA                 = 'MIA'; // Thất lạc
    const STATUS_DELIVERY_CANCELLED  = 'DELIVERY_CANCELLED'; // Khách không nhận

    const STATUS_M1_DEPOSITED           = 'DEPOSITED'; // Đã Đặt Cọc
    const STATUS_M1_ACCEPTED            = 'ACCEPTED'; // Đã tiếp nhận
    const STATUS_M1_PENDING             = 'PENDING'; // Chờ Duyệt
    const STATUS_M1_PROCESSING          = 'PROCESSING'; // Đang xử lý
    const STATUS_M1_PURCHASED           = 'PURCHASED'; // Đã mua
    const STATUS_M1_MERCHANT_DELIVERING = 'MERCHANT_DELIVERING'; // Người bán giao
    const STATUS_M1_PUTAWAY             = 'PUTAWAY'; // Hàng về kho
    const STATUS_M1_TRANSPORTING        = 'TRANSPORTING'; // Vận chuyển quốc tế
    const STATUS_M1_READY_FOR_DELIVERY  = 'READY_FOR_DELIVERY'; // Chờ giao
    const STATUS_M1_DELIVERING          = 'DELIVERING'; // Đang giao
    const STATUS_M1_DELIVERED           = 'DELIVERED'; // Khách đã nhận
    const STATUS_M1_CANCELLED           = 'CANCELLED'; // Hủy bỏ
    const STATUS_M1_OUT_OF_STOCK        = 'OUT_OF_STOCK'; // Hết hàng
    const STATUS_M1_MIA                 = 'MIA'; // Thất lạc
    const STATUS_M1_DELIVERY_CANCELLED  = 'DELIVERY_CANCELLED'; // Khách không nhận

    public static $transformStatusM1 = [
        self::STATUS_DEPOSITED => self::STATUS_M1_DEPOSITED,
        self::STATUS_PENDING => self::STATUS_M1_PENDING,
        self::STATUS_ACCEPTED => self::STATUS_M1_ACCEPTED,
        self::STATUS_PURCHASING => self::STATUS_M1_PROCESSING,
        self::STATUS_WAITING_FOR_PAYMENT => self::STATUS_M1_PROCESSING,
        self::STATUS_PURCHASED => self::STATUS_M1_PURCHASED,
        self::STATUS_MERCHANT_DELIVERING => self::STATUS_M1_MERCHANT_DELIVERING,
        self::STATUS_PUTAWAY => self::STATUS_M1_PUTAWAY,
        self::STATUS_TRANSPORTING => self::STATUS_M1_TRANSPORTING,
        self::STATUS_READY_FOR_DELIVERY => self::STATUS_M1_READY_FOR_DELIVERY,
        self::STATUS_DELIVERING => self::STATUS_M1_DELIVERING,
        self::STATUS_DELIVERED => self::STATUS_M1_DELIVERED,
        self::STATUS_CANCELLED => self::STATUS_M1_CANCELLED,
        self::STATUS_OUT_OF_STOCK => self::STATUS_M1_OUT_OF_STOCK,
        self::STATUS_MIA => self::STATUS_M1_MIA,
        self::STATUS_DELIVERY_CANCELLED => self::STATUS_M1_DELIVERY_CANCELLED,
    ];

    protected $table = 'purchasing_orders';

    protected $casts = [
        'ordered_at' => 'datetime',
        'is_putaway' => 'boolean'
    ];

    /**
     * @return BelongsTo
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function purchasingService()
    {
        return $this->belongsTo(PurchasingService::class);
    }

    /**
     * @return BelongsTo
     */
    public function purchasingAccount()
    {
        return $this->belongsTo(PurchasingAccount::class);
    }

    /**
     * @return HasMany
     */
    public function purchasingOrderItems()
    {
        return $this->hasMany(PurchasingOrderItem::class, 'purchasing_order_id', 'id');
    }

    /**
     * @return BelongsToMany
     */
    public function purchasingVariants()
    {
        return $this->belongsToMany(PurchasingVariant::class, 'purchasing_order_items', 'purchasing_order_id', 'purchasing_variant_id');
    }

    /**
     * @return HasMany
     */
    public function purchasingPackages()
    {
        return $this->hasMany(PurchasingPackage::class, 'purchasing_order_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsToMany
     */
    public function services()
    {
        return $this->belongsToMany(Service::class, 'purchasing_order_services');
    }

    /**
     * @return HasMany
     */
    public function purchasingOrderServices()
    {
        return $this->hasMany(PurchasingOrderService::class);
    }

    /** Lọc theo code
     * @param $query
     * @param $code
     * @return mixed
     */
    public function scopeCode($query, $code)
    {
        if ($code){
            return $query->where('purchasing_orders.code', $code);
        }else
            return $query;
    }

    /** Lọc theo merchant id
     * @param $query
     * @param $purchasingAccountId
     * @return mixed
     */
    public function scopePurchasingAccountId($query, $purchasingAccountId)
    {
        if ($purchasingAccountId){
            return $query->where('purchasing_orders.purchasing_account_id', $purchasingAccountId);
        }else
            return $query;
    }

    public function scopeHasPackage($query, $hasPackage)
    {
        if ($hasPackage){
            $query->join('purchasing_packages', 'purchasing_packages.purchasing_order_id', 'purchasing_orders.id');
            return $query;
        }else
            return $query;
    }

    public function scopeIsPutaway($query, $isPutaway)
    {
        if ($isPutaway){
            return $query->where('purchasing_orders.is_putaway', $isPutaway);
        }else
            return $query;
    }
}
