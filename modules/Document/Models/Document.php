<?php /** @noinspection SpellCheckingInspection */

namespace Modules\Document\Models;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Modules\Order\Models\Order;
use Modules\OrderExporting\Models\OrderExporting;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Stock\Models\StockLog;
use Modules\Stock\Services\StockObjectInterface;
use Modules\Tenant\Models\Tenant;
use Modules\Transaction\Services\SupplierTransObjInterface;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;

/**
 * Class Document
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $code
 * @property string $type
 * @property string $status
 * @property int $warehouse_id
 * @property int shipping_partner_id
 * @property int $creator_id
 * @property Carbon $created_at
 * @property Carbon received_date
 * @property Carbon received_pay_date
 * @property int $verifier_id
 * @property Carbon $verified_at
 * @property string $note
 * @property array info
 * @property float other_fee
 *
 * @property Warehouse $warehouse
 * @property User $creator
 * @property User $verifier
 * @property Tenant|null tenant
 * @property ShippingPartner|null shippingPartner
 * @property Collection|Order[] $orders
 * @property Collection|OrderPacking[] $orderPackings
 * @property Collection|OrderExporting[] $orderExportings
 * @property Collection|OrderExporting[] orderExportingInventories
 * @property Collection documentOrderInventories
 * @property Collection documentSkuInventories
 * @property Collection|DocumentSkuImporting[] documentSkuImportings
 * @property Collection|ImportingBarcode[] importingBarcodes
 * @property Collection|DocumentFreightBillInventory[] documentFreightBillInventories
 * @property Collection|DocumentOrder[] $documentOrders
 * @property Collection documentDeliveryComparisons
 * @property Collection documentChangePositionStocks
 * @property DocumentSupplierTransaction documentSupplierTransaction
 *
 */
class Document extends Model implements StockObjectInterface, SupplierTransObjInterface
{
    const TYPE_PACKING                = 'PACKING'; // Đóng hàng
    const TYPE_EXPORTING              = 'EXPORTING'; // Xuất hàng
    const TYPE_EXPORTING_INVENTORY    = 'EXPORTING_INVENTORY'; // Đối soát xuất hàng
    const TYPE_IMPORTING              = 'IMPORTING'; // Nhập hàng
    const TYPE_IMPORTING_RETURN_GOODS = 'IMPORTING_RETURN_GOODS'; // Nhập hàng theo đơn hoàn
    const TYPE_SKU_INVENTORY          = 'SKU_INVENTORY'; // Kiểm kê sản phẩm kho
    const TYPE_FREIGHT_BILL_INVENTORY = 'FREIGHT_BILL_INVENTORY'; // Đối soát COD
    const TYPE_DELIVERY_COMPARISON    = 'DELIVERY_COMPARISON'; // Đối soát giao nhận
    const TYPE_CHANGE_POSITION_GOODS  = 'CHANGE_POSITION_GOODS'; // Thay đổi vị trí hàng hoá
    const TYPE_SUPPLIER_PAYMENT       = 'SUPPLIER_PAYMENT'; // thanh toán cho supplier

    const CODE_PREFIXES = [
        self::TYPE_PACKING => 'DH',
        self::TYPE_EXPORTING => 'XH',
        self::TYPE_EXPORTING_INVENTORY => 'DS',
        self::TYPE_IMPORTING => 'NH',
        self::TYPE_IMPORTING_RETURN_GOODS => 'NHH',
        self::TYPE_SKU_INVENTORY => 'KK',
        self::TYPE_FREIGHT_BILL_INVENTORY => 'KV',
        self::TYPE_DELIVERY_COMPARISON => 'TDS',
        self::TYPE_CHANGE_POSITION_GOODS => 'CPG',
        self::TYPE_SUPPLIER_PAYMENT => 'SP',
    ];

    const STATUS_DRAFT     = 'DRAFT'; // Nháp
    const STATUS_COMPLETED = 'COMPLETED'; // Hoàn thành
    const STATUS_CANCELLED = 'CANCELLED'; // Hủy bỏ

    const INFO_DOCUMENT_EXPORTING_DOCUMENT_PACKING = 'document_packing'; // Tạo thành từ chứng từ đóng hàng nào ?
    const INFO_DOCUMENT_EXPORTING_RECEIVER_NAME    = 'receiver_name'; // Tên người nhận
    const INFO_DOCUMENT_EXPORTING_RECEIVER_PHONE   = 'receiver_phone'; // Số điện thoại người nhận
    const INFO_DOCUMENT_EXPORTING_RECEIVER_LICENSE = 'receiver_license'; // Số xe / căn cước người nhận
    const INFO_DOCUMENT_EXPORTING_PARTNER          = 'partner'; // Công ty nhận
    const INFO_DOCUMENT_EXPORTING_BARCODE_TYPE     = 'barcode_type'; // Loại mã quét tạo chứng từ
    const INFO_DOCUMENT_IMPORTING_SENDER_NAME      = 'sender_name'; // Tên người giao hàng
    const INFO_DOCUMENT_IMPORTING_SENDER_PHONE     = 'sender_phone'; // Số điện thoại người giao hàng
    const INFO_DOCUMENT_IMPORTING_SENDER_LICENSE   = 'sender_license'; // Số xe / căn cước người giao hàng
    const INFO_DOCUMENT_IMPORTING_SENDER_PARTNER   = 'sender_partner'; // Công ty giao hàng
    const INFO_DOCUMENT_SKU_INVENTORY_BALANCED_AT  = 'balanced_at'; // Thời điểm cân bằng kiểm kê

    /**
     * Kiểu quét chứng từ đóng hàng/xuất hàng
     */
    const DOCUMENT_BARCODE_TYPE_ORDER        = 'ORDER';
    const DOCUMENT_BARCODE_TYPE_FREIGHT_BILL = 'FREIGHT_BILL';

    /**
     * Get object type
     *
     * @return string
     */
    public function getObjectType()
    {
        return StockLog::OBJECT_DOCUMENT;
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

    protected $table = 'documents';

    protected $casts = [
        'info' => 'array',
        'verified_at' => 'datetime',
        'received_date' => 'datetime',
        'received_pay_date' => 'datetime'
    ];


    /**
     * @return BelongsTo
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /**
     * Get code prefix of given type
     *
     * @param string $type
     * @return string|null
     */
    public static function getCodePrefix(string $type)
    {
        return Arr::get(static::CODE_PREFIXES, $type);
    }

    /**
     * @return BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
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
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verifier_id', 'id');
    }

    /**
     * @return BelongsToMany
     */
    public function orders()
    {
        return $this->belongsToMany(Order::class, 'document_orders', 'document_id', 'order_id');
    }

    /**
     * @return BelongsToMany
     */
    public function orderPackings()
    {
        return $this->belongsToMany(OrderPacking::class, 'document_order_packings', 'document_id', 'order_packing_id')->with('orderExporting');
    }

    /**
     * @return BelongsToMany
     */
    public function orderExportings()
    {
        return $this->belongsToMany(OrderExporting::class, 'document_order_exportings', 'document_id', 'order_exporting_id');
    }

    /**
     * Yêu cầu XH của chứng từ đối soát
     *
     * @return BelongsToMany
     */
    public function orderExportingInventories()
    {
        return $this->belongsToMany(OrderExporting::class, 'document_order_inventories', 'document_id', 'order_exporting_id');
    }

    /**
     * @return HasMany
     */
    public function documentOrderInventories()
    {
        return $this->hasMany(DocumentOrderInventory::class);
    }

    /**
     * @return HasMany
     */
    public function documentSkuInventories()
    {
        return $this->hasMany(DocumentSkuInventory::class);
    }

    /**
     * @return HasMany
     */
    public function documentSkuImportings()
    {
        return $this->hasMany(DocumentSkuImporting::class);
    }

    /**
     * @return HasMany
     */
    public function importingBarcodes()
    {
        return $this->hasMany(ImportingBarcode::class);
    }

    /**
     * @return HasMany
     */
    public function documentOrders()
    {
        return $this->hasMany(DocumentOrder::class);
    }

    /**
     * @return HasMany
     */
    public function documentFreightBillInventories(): HasMany
    {
        return $this->hasMany(DocumentFreightBillInventory::class);
    }

    /**
     * @return HasMany
     */
    public function documentDeliveryComparisons(): HasMany
    {
        return $this->hasMany(DocumentDeliveryComparison::class);
    }

    /**
     * @return HasMany
     */
    public function documentChangePositionStocks()
    {
        return $this->hasMany(DocumentChangePositionStock::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function documentSupplierTransaction()
    {
        return $this->hasOne(DocumentSupplierTransaction::class);
    }
    /**
     * @return StockObjectInterface
     */
    public function importingStockLogObject()
    {
        /** @var ImportingBarcode|null $importingBarcode */
        if ($importingBarcode = $this->importingBarcodes->first()) {
            return $importingBarcode->stockLogObject();
        }
        return $this;
    }
}
