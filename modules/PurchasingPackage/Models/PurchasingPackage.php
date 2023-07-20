<?php

namespace Modules\PurchasingPackage\Models;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Document\Models\ImportingBarcode;
use Modules\Merchant\Models\Merchant;
use Modules\PurchasingOrder\Models\PurchasingOrder;
use Modules\PurchasingOrder\Models\PurchasingVariant;
use Modules\PurchasingPackage\Commands\ChangeStatusPurchasingPackage;
use Modules\PurchasingPackage\Services\PurchasingPackageEvent;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Stock\Models\StockLog;
use Modules\Stock\Services\StockObjectInterface;
use Modules\Transaction\Services\MerchantTransObjInterface;
use Modules\Transaction\Services\SupplierTransObjInterface;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;
use Modules\Service\Models\Service as ServiceModel;
use Modules\Service\Models\ServicePrice;

/**
 * Class PurchasingPackage
 *
 * @property int $id
 * @property int tenant_id
 * @property int purchasing_order_id
 * @property int destination_warehouse_id
 * @property int shipping_partner_id
 * @property int creator_id
 * @property int merchant_id
 * @property string freight_bill_code
 * @property float service_amount
 * @property string code
 * @property float weight
 * @property float length
 * @property float width
 * @property float height
 * @property string status
 * @property string finance_status
 * @property int received_quantity
 * @property int quantity
 * @property boolean is_putaway
 * @property string note
 * @property Carbon imported_at
 * @property Carbon $created_at
 *
 * @property PurchasingOrder|null purchasingOrder
 * @property Warehouse|null destinationWarehouse
 * @property ShippingPartner|null shippingPartner
 * @property User|null creator
 * @property Merchant|null merchant
 * @property PurchasingPackageItem[]|Collection purchasingPackageItems
 * @property PurchasingPackageService[]|Collection purchasingPackageServices
 * @property ServiceModel[]|Collection services
 * @property ServicePrice[]|Collection servicePrices
 * @property Collection purchasingVariants
 * @property ImportingBarcode|null importingBarcode
 */
class PurchasingPackage extends Model implements StockObjectInterface, MerchantTransObjInterface, SupplierTransObjInterface
{
    const STATUS_INIT                 = 'INIT'; // Khởi tạo, đối với kiện lô chưa về kho
    const STATUS_PUTAWAY              = 'PUTAWAY'; // Kiện về kho, kiện vừa được chuyển đến kho và tạo kiện thành công trên hệ thống
    const STATUS_TRANSPORTING         = 'TRANSPORTING'; // Vận chuyển
    const STATUS_READY_FOR_DELIVERY   = 'READY_FOR_DELIVERY'; // Chờ giao
    const STATUS_REQUEST_FOR_DELIVERY = 'REQUEST_FOR_DELIVERY'; // Yêu cầu giao
    const STATUS_DELIVERING           = 'DELIVERING'; // Đang giao
    const STATUS_DELIVERED            = 'DELIVERED'; // Đã nhận
    const STATUS_MIA                  = 'MIA'; // Thất lạc kiện bị mất nói chung, hiện tại không tìm thấy
    const STATUS_INACTIVE             = 'INACTIVE'; // Ngừng hoạt động không áp dụng tính phí và không rơi vào những trường hợp không tính phí khác
    const STATUS_WAIT_FOR_RETURN      = 'WAIT_FOR_RETURN'; // Chờ trả hàng
    const STATUS_RETURNING            = 'RETURNING'; // Đang trả hàng
    const STATUS_RETURNED             = 'RETURNED'; // Đã trả hàng
    const STATUS_WAIT_FOR_LIQUIDATION = 'WAIT_FOR_LIQUIDATION'; // Chờ thanh lý
    const STATUS_LIQUIDATED           = 'LIQUIDATED'; // Đã thanh lý
    const STATUS_REFUSED              = 'REFUSED'; // Khách không nhận
    const STATUS_IMPORTED             = 'IMPORTED'; // Đã nhập kho
    const STATUS_CANCELED             = 'CANCELED'; // Hủy

    /**
     * Trang thái tài chính
     */
    const FINANCE_STATUS_UNPAID = 'UNPAID'; // Chưa thanh toán
    const FINANCE_STATUS_PAID   = 'PAID'; // Đã thanh toán

    protected $table = 'purchasing_packages';

    protected $casts = [
        'imported_at' => 'datetime',
        'is_putaway' => 'boolean',
    ];

    /**
     * @return BelongsTo
     */
    public function purchasingOrder()
    {
        return $this->belongsTo(PurchasingOrder::class, 'purchasing_order_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function purchasingPackageItems()
    {
        return $this->hasMany(PurchasingPackageItem::class, 'purchasing_package_id', 'id');
    }

    /**
     * @return BelongsToMany
     */
    public function purchasingVariants()
    {
        return $this->belongsToMany(PurchasingVariant::class, 'purchasing_package_items', 'purchasing_package_id', 'purchasing_variant_id');
    }

    /**
     * @return BelongsTo
     */
    public function destinationWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id', 'id');
    }


    /**
     * @return BelongsTo
     */
    public function shippingPartner()
    {
        return $this->belongsTo(ShippingPartner::class, 'shipping_partner_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }


    /**
     * @return BelongsTo
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function importingBarcodes()
    {
        return $this->hasMany(ImportingBarcode::class, 'object_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function purchasingPackageServices()
    {
        return $this->hasMany(PurchasingPackageService::class, 'purchasing_package_id', 'id');
    }


    /**
     * @return BelongsToMany
     */
    public function services()
    {
        return $this->belongsToMany(ServiceModel::class, 'purchasing_package_services', 'purchasing_package_id', 'service_id');
    }

    /**
     * @return BelongsToMany
     */
    public function servicePrices()
    {
        return $this->belongsToMany(ServicePrice::class, 'purchasing_package_services', 'purchasing_package_id', 'service_price_id');
    }

    /**
     * @return HasOne
     */
    public function importingBarcode()
    {
        return $this->hasOne(ImportingBarcode::class, 'object_id', 'id')->where('barcode', $this->code);
    }

    /**
     * Get object type
     *
     * @return string
     */
    public function getObjectType()
    {
        return StockLog::OBJECT_PURCHASING_PACKAGE;
    }

    /**
     * Get object id
     *
     * @return string
     */
    public function getObjectId()
    {
        return $this->getKey();
    }

    /**
     * @param $status
     * @param User $creator
     * @param array $payload
     * @return PurchasingPackage
     */
    public function changeStatus($status, User $creator, $payload = [])
    {
        return (new ChangeStatusPurchasingPackage($this, $status, $creator, $payload))->handle();
    }

    /** Lọc theo mã kiện
     * @param $query
     * @param $code
     * @return mixed
     */
    public function scopeCode($query, $code)
    {
        if (isset($code)) {
            return $query->where('purchasing_packages.code', $code);
        } else {
            return $query;
        }
    }

    /** Lọc theo sku_code
     * @param $query
     * @param $skuId
     * @return void
     */
    public function scopeSkuCode($query, $skuIds)
    {

        if (isset($skuIds) && $skuIds != '') {
            $query->join('purchasing_package_items', 'purchasing_package_items.purchasing_package_id', 'purchasing_packages.id');
            if (is_array($skuIds)) {
                return $query->whereIn('purchasing_package_items.sku_id', $skuIds);
            } else {
                return $query->where('purchasing_package_items.sku_id', $skuIds);
            }
        } else {
            return $query;
        }
    }

    /** Lọc theo mã vận đơn
     * @param $query
     * @param $freightBillCode
     * @return mixed
     */
    public function scopeFreightBillCode($query, $freightBillCode)
    {
        if (isset($freightBillCode)) {
            return $query->where('freight_bill_code', $freightBillCode);
        } else {
            return $query;
        }
    }

    /** Lọc theo kho
     * @param $query
     * @param $destinationWarehouseId
     * @return mixed
     */
    public function scopeWarehouse($query, $destinationWarehouseId)
    {
        if (isset($destinationWarehouseId)) {
            return $query->where('destination_warehouse_id', $destinationWarehouseId);
        } else {
            return $query;
        }
    }

    /** Lọc theo đvvc
     * @param $query
     * @param $shippingPartnerId
     * @return mixed
     */
    public function scopeShippingPartner($query, $shippingPartnerId)
    {
        if (isset($shippingPartnerId)) {
            return $query->where('shipping_partner_id', $shippingPartnerId);
        } else {
            return $query;
        }
    }

    /** Lọc theo trạng thái
     * @param $query
     * @param $status
     * @return mixed
     */
    public function scopeStatus($query, $status)
    {
        if (isset($status)) {
            return $query->where('status', $status);
        } else {
            return $query;
        }
    }

    /** Lọc theo ngày tạo
     * @param $query
     * @param $createdAt
     * @return mixed
     */
    public function scopeCreatedAt($query, $createdAt)
    {
        $createdAtFromRaw = data_get($createdAt, 'from');
        $createdAtToRaw   = data_get($createdAt, 'to');


        $createdAtFrom = Carbon::parse($createdAtFromRaw)->startOfDay();
        $createdAtTo   = Carbon::parse($createdAtToRaw)->endOfDay();

        if ($createdAtToRaw && $createdAtToRaw) {
            return $query->whereBetween('purchasing_packages.created_at', [$createdAtFrom, $createdAtTo]);
        } else {
            return $query;
        }
    }
}


