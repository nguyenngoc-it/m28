<?php

namespace Modules\FreightBill\Models;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Modules\Document\Models\Document;
use Modules\Document\Models\ImportingBarcode;
use Modules\Order\Models\Order;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Stock\Models\StockLog;
use Modules\Stock\Services\StockObjectInterface;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use phpDocumentor\Reflection\Types\Self_;

/**
 * Class Location
 *
 * @property int $id
 * @property int order_id
 * @property int order_packing_id
 * @property int shipping_partner_id
 * @property int $tenant_id
 * @property string $freight_bill_code
 * @property string $status
 * @property string $receiver_name
 * @property string $receiver_phone
 * @property string $receiver_address
 * @property string $sender_name
 * @property string $sender_phone
 * @property string $sender_address
 * @property string $fee
 * @property array $snapshots
 * @property float cod_total_amount
 * @property float cod_paid_amount
 * @property float cod_fee_amount
 * @property float shipping_amount
 * @property float other_fee
 * @property Carbon created_at
 *
 * @property Order order
 * @property OrderPacking orderPacking
 * @property OrderPacking currentOrderPacking
 * @property ShippingPartner shippingPartner
 */
class FreightBill extends Model implements StockObjectInterface
{
    const SNAPSHOT_ITEMS             = 'items';
    const STATUS_WAIT_FOR_PICK_UP    = 'WAIT_FOR_PICK_UP'; // Chờ lấy hàng
    const STATUS_PICKED_UP           = 'PICKED_UP'; // Đã đi lấy hàng nhưng chưa xác nhận lấy được hàng
    const STATUS_CONFIRMED_PICKED_UP = 'CONFIRMED_PICKED_UP'; // Xác nhận đã lấy hàng
    const STATUS_DELIVERING          = 'DELIVERING'; // Đang giao
    const STATUS_DELIVERED           = 'DELIVERED'; // Đã giao
    const STATUS_CANCELLED           = 'CANCELLED'; // Huỷ
    const STATUS_FAILED_PICK_UP      = 'FAILED_PICK_UP'; // Lấy hàng không thành công
    const STATUS_RETURN              = 'RETURN'; // Sẽ trả hàng hoặc đang trả hàng
    const STATUS_RETURN_COMPLETED    = 'RETURN_COMPLETED'; // Đã trả hàng
    const STATUS_FAILED_DELIVERY     = 'FAILED_DELIVERY'; // giao hàng lỗi

    public static $freightBillStatus = [
        self::STATUS_WAIT_FOR_PICK_UP,
        self::STATUS_PICKED_UP,
        self::STATUS_CONFIRMED_PICKED_UP,
        self::STATUS_DELIVERING,
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
        self::STATUS_FAILED_PICK_UP,
        self::STATUS_RETURN,
        self::STATUS_RETURN_COMPLETED,
        self::STATUS_FAILED_DELIVERY
    ];


    protected $table = 'freight_bills';

    protected $casts = [
        'snapshots' => 'array',
    ];

    /**
     * Get object type
     *
     * @return string
     */
    public function getObjectType()
    {
        return StockLog::OBJECT_FREIGHT_BILL;
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
     * @return BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function orderPacking()
    {
        return $this->belongsTo(OrderPacking::class, 'order_packing_id', 'id');
    }

    /**
     * Lấy ra YCDH đang sử dụng vận đơn
     *
     * @return BelongsTo
     */
    public function currentOrderPacking()
    {
        return $this->belongsTo(OrderPacking::class, 'order_packing_id', 'id')
            ->where('freight_bill_id', $this->getKey());
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
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /**
     * Thay đổi trạng thái mã vận đơn
     *
     * @param string $status
     * @param User $creator
     * @return FreightBill
     */
    public function changeStatus($status, User $creator)
    {
        return Service::freightBill()->changeStatus($this, $status, $creator);
    }

    /**
     * Lấy các bản ghi mã quét nhập hàng hoàn của 1 mã vận đơn
     *
     * @return ImportingBarcode|null|mixed
     */
    public function importingBarcodeReturnGoods()
    {
        $importingBarcodes = ImportingBarcode::query()->where([
            'type' => ImportingBarcode::TYPE_FREIGHT_BILL,
            'barcode' => $this->freight_bill_code,
            'imported_type' => ImportingBarcode::IMPORTED_TYPE_RETURN_GOODS
        ])->get();
        /** @var ImportingBarcode $importingBarcode */
        foreach ($importingBarcodes as $importingBarcode) {
            if (in_array($importingBarcode->document->status, [Document::STATUS_DRAFT, Document::STATUS_COMPLETED])) {
                return $importingBarcode;
            }
        }
        return null;
    }

    /**
     * Lấy trạng thái đơn tương ứng
     * @return mixed
     */
    public function mapOrderStatus()
    {
        return Arr::get([
            FreightBill::STATUS_WAIT_FOR_PICK_UP => Order::STATUS_DELIVERING,
            FreightBill::STATUS_PICKED_UP => Order::STATUS_DELIVERING,
            FreightBill::STATUS_CONFIRMED_PICKED_UP => Order::STATUS_DELIVERING,
            FreightBill::STATUS_DELIVERING => Order::STATUS_DELIVERING,
            FreightBill::STATUS_DELIVERED => Order::STATUS_DELIVERED,
            FreightBill::STATUS_RETURN => Order::STATUS_RETURN,
            FreightBill::STATUS_RETURN_COMPLETED => Order::STATUS_RETURN_COMPLETED,
            FreightBill::STATUS_FAILED_DELIVERY => Order::STATUS_FAILED_DELIVERY,
            FreightBill::STATUS_CANCELLED => Order::STATUS_WAITING_PROCESSING,
        ], $this->getAttribute('status'), '');
    }
}
