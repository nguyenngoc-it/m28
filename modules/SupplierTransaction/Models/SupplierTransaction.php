<?php

namespace Modules\SupplierTransaction\Models;

use App\Base\Model;
use Carbon\Carbon;
use Modules\Document\Models\Document;
use Modules\Order\Models\Order;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\Supplier\Models\Supplier;

/**
 * Class SupplierTransaction
 *
 * @property int id
 * @property int tenant_id
 * @property int supplier_id
 * @property string type
 * @property string object_type
 * @property int object_id
 * @property float amount
 * @property array metadata
 * @property string inventory_trans_id
 * @property string inventory_m4_trans_id
 * @property string sold_trans_id
 * @property string sold_m4_trans_id
 * @property Carbon created_at
 * @property Carbon updated_at
 * @property Supplier|null supplier
 * @property PurchasingPackage|null purchasingPackage
 * @property Order|null order
 * @property Document|null document
 */
class SupplierTransaction extends Model
{
    protected $table = 'supplier_transactions';

    protected $casts = [
        'metadata' => 'json',
    ];

    const TYPE_IMPORT           = 'IMPORT'; // NHập kho
    const TYPE_EXPORT           = 'EXPORT'; // Xuất kho
    const TYPE_IMPORT_BY_RETURN = 'IMPORT_BY_RETURN'; // Nhập hoàn
    const TYPE_PAYMENT_DEPOSIT          = 'PAYMENT_DEPOSIT'; // Thanh toán nap tien
    const TYPE_PAYMENT_COLLECT          = 'PAYMENT_COLLECT'; // chi -  tiền ví

    const OBJECT_PURCHASING_PACKAGE = 'PURCHASING_PACKAGE';
    const OBJECT_ORDER  = 'ORDER';
    const OBJECT_DOCUMENT = 'DOCUMENT';


    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function purchasingPackage()
    {
        return $this->belongsTo(PurchasingPackage::class, 'purchasing_package_id', 'id');
    }

    public function getPurchasingPackageIdAttribute()
    {
        return $this->getObjectIdOfType(static::OBJECT_PURCHASING_PACKAGE);
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function getOrderIdAttribute()
    {
        return $this->getObjectIdOfType(static::OBJECT_ORDER);
    }

    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id', 'id');
    }

    public function getDocumentIdAttribute()
    {
        return $this->getObjectIdOfType(static::OBJECT_DOCUMENT);
    }

    /**
     * @param string $objectType
     * @return int|null
     */
    protected function getObjectIdOfType($objectType)
    {
        return $this->getAttribute('object_type') === $objectType ? $this->getAttribute('object_id') : null;
    }

}
