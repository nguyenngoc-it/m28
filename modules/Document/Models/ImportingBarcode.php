<?php

namespace Modules\Document\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Models\Order;
use Modules\Product\Models\Sku;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\Stock\Services\StockObjectInterface;
use Modules\Tenant\Models\Tenant;

/**
 * Class DocumentSkuImporting
 *
 * @property int id
 * @property int tenant_id
 * @property int document_id
 * @property int freight_bill_id
 * @property string type
 * @property string barcode
 * @property int object_id
 * @property array snapshot_skus
 * @property string imported_type
 *
 *
 * @property Tenant tenant
 * @property Document document
 * @property FreightBill freightBill
 * @property DocumentSkuImporting[]|Collection documentSkuImporting
 */
class ImportingBarcode extends Model
{
    /**
     * Loại mã quét
     */
    const TYPE_SKU_CODE             = 'SKU_CODE';
    const TYPE_SKU_REF              = 'SKU_REF';
    const TYPE_SKU_ID               = 'SKU_ID';
    const TYPE_PACKAGE_CODE         = 'PACKAGE_CODE'; //mã kiện nhập
    const TYPE_PACKAGE_FREIGHT_BILL = 'PACKAGE_FREIGHT_BILL'; //mã vận đơn của kiện nhập, do seller tạo
    const TYPE_ORDER_CODE           = 'ORDER_CODE';
    const TYPE_FREIGHT_BILL         = 'FREIGHT_BILL'; //mã vận đơn của đơn

    /**
     * Loại nhập hàng
     */
    const IMPORTED_TYPE_RETURN_GOODS = 'return_goods'; // Nhập hàng hoàn

    /**
     * @var array
     */
    static $listTypes = [
        self::TYPE_SKU_CODE,
        self::TYPE_SKU_REF,
        self::TYPE_SKU_ID,
        self::TYPE_PACKAGE_CODE,
        self::TYPE_PACKAGE_FREIGHT_BILL,
        self::TYPE_ORDER_CODE,
    ];

    protected $table = 'importing_barcodes';

    protected $casts = [
        'snapshot_skus' => 'array'
    ];

    /**
     * @return BelongsTo
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }


    /**
     * @return BelongsTo
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * @return HasMany
     */
    public function documentSkuImporting()
    {
        return $this->hasMany(DocumentSkuImporting::class);
    }

    public function freightBill()
    {
        return $this->belongsTo(FreightBill::class);
    }

    /**
     * @return StockObjectInterface
     */
    public function stockLogObject()
    {
        switch ($this->type) {
            case static::TYPE_FREIGHT_BILL:
                return FreightBill::find($this->object_id);
            case static::TYPE_ORDER_CODE:
                return Order::find($this->object_id);
            case static::TYPE_PACKAGE_CODE:
                return PurchasingPackage::find($this->object_id);
        }

        return $this->document;
    }
}
