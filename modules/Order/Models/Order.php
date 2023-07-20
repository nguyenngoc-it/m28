<?php

namespace Modules\Order\Models;

use App\Base\Model;
use App\Traits\ModelInteractsWithWebhook;
use Gobiz\Support\Traits\CachedPropertiesTrait;
use Gobiz\Workflow\SubjectInterface;
use Gobiz\Workflow\WorkflowException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;
use Modules\Auth\Services\Permission;
use Modules\Currency\Models\Currency;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerAddress;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentFreightBillInventory;
use Modules\Document\Models\DocumentOrder;
use Modules\Document\Models\ImportingBarcode;
use Modules\FreightBill\Models\FreightBill;
use Modules\Location\Models\Location;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\Merchant\Models\Sale;
use Modules\Order\Commands\GetOrderTags;
use Modules\Order\Events\OrderAttributesChanged;
use Modules\Order\Services\OrderActivityLogger;
use Modules\Order\Services\OrderEvent;
use Modules\Order\Services\OrderWebhookEventPublisher;
use Modules\Order\Services\StatusOrder;
use Modules\OrderExporting\Models\OrderExporting;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\OrderPacking\Models\OrderPackingService;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuCombo;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartnerExpectedTransportingPrice;
use Modules\Stock\Models\StockLog;
use Modules\Stock\Services\StockObjectInterface;
use Modules\Store\Models\Store;
use Modules\Tenant\Models\Tenant;
use Carbon\Carbon;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Transaction\Services\MerchantTransObjInterface;
use Modules\Transaction\Services\SupplierTransObjInterface;
use Modules\User\Models\User;
use Modules\Service\Models\Service as ServiceModel;
use Modules\Service\Models\ServicePrice;
use Modules\Warehouse\Models\Warehouse;

/**
 * Class Order
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $merchant_id
 * @property int warehouse_id
 * @property int $store_id
 * @property string $marketplace_code
 * @property string $marketplace_store_id
 * @property int $creator_id
 * @property string $code
 * @property string $status
 * @property string finance_status
 * @property string finance_service_status
 * @property string finance_service_import_return_goods_status
 * @property float $order_amount
 * @property float $discount_amount
 * @property float $shipping_amount
 * @property float expected_shipping_amount
 * @property float $total_amount
 * @property float $paid_amount
 * @property float $debit_amount
 * @property float cost_price
 * @property float cost_of_goods
 * @property string finance_cost_of_goods_status
 * @property float cod_fee_amount
 * @property float cod
 * @property float other_fee
 * @property float service_amount
 * @property float extent_service_expected_amount
 * @property float extent_service_amount
 * @property string finance_extent_service_status
 * @property float service_import_return_goods_amount
 * @property float amount_paid_to_seller
 * @property string $receiver_name
 * @property string $receiver_phone
 * @property string $receiver_address
 * @property string $receiver_note
 * @property Carbon|null $intended_delivery_at
 * @property string $payment_type
 * @property string $payment_method
 * @property string $description
 * @property int $customer_id
 * @property int $customer_address_id
 * @property int $sale_id
 * @property array $extra_services
 * @property string $cancel_reason
 * @property string $cancel_note
 * @property Carbon|null $created_at_origin
 * @property string $freight_bill
 * @property string $campaign
 * @property int $currency_id
 * @property int $receiver_country_id
 * @property int $receiver_province_id
 * @property int $receiver_district_id
 * @property int $receiver_ward_id
 * @property int $shipping_partner_id
 * @property Carbon $created_at
 * @property Carbon updated_at
 * @property boolean dropship
 * @property boolean inspected
 * @property boolean priority
 * @property Carbon $packed_at
 * @property int packer_id
 * @property boolean has_document_inventory
 * @property string receiver_postal_code
 * @property string name_store
 * @property float delivery_fee
 * @property string shipping_financial_status
 *
 *
 * @property Tenant|null $tenant
 * @property Merchant|null $merchant
 * @property Store|null $store
 * @property CustomerAddress|null $customerAddress
 * @property Customer|null $customer
 * @property Sale|null $sale
 * @property OrderSku[]|Collection orderSkus
 * @property OrderStock[]|Collection $orderStocks
 * @property OrderTransaction[]|Collection $orderTransactions
 * @property Location|null $receiverCountry
 * @property Location|null $receiverProvince
 * @property Location|null $receiverDistrict
 * @property Location|null $receiverWard
 * @property User|null $creator
 * @property Currency|null $currency
 * @property Sku[]|Collection $skus
 * @property ShippingPartner|null $shippingPartner
 * @property Collection|OrderPacking[] orderPackings
 * @property OrderPacking|null orderPacking
 * @property Collection orderExportings
 * @property Collection freightBills
 * @property Document[]|Collection $documents
 * @property DocumentFreightBillInventory[]|Collection documentFreightBillInventories
 *
 * @property OrderPackingService[]|Collection orderPackingServices
 * @property ServiceModel[]|Collection $exportServices
 * @property ServicePrice[]|Collection $exportServicePrices
 * @property ServiceModel[]|Collection importReturnGoodsServices
 * @property Collection importReturnGoodsServicePrices
 * @property OrderImportReturnGoodsService[]|Collection orderImportReturnGoodsServices
 * @property OrderProductPriceDetail[]|Collection orderProductPriceDetails
 * @property Warehouse|null warehouse
 * @property ExpectedTransportingOrderSnapshot|null expectedTransportingOrderSnapshot
 *
 */
class Order extends Model implements StockObjectInterface, SubjectInterface, MerchantTransObjInterface, SupplierTransObjInterface
{
    use CachedPropertiesTrait;
    use ModelInteractsWithWebhook;

    protected $table = 'orders';

    protected $casts = [
        'order_amount' => 'float',
        'discount_amount' => 'float',
        'shipping_amount' => 'float',
        'total_amount' => 'float',
        'paid_amount' => 'float',
        'debit_amount' => 'float',
        'service_amount' => 'float',
        'service_import_return_goods_amount' => 'float',
        'amount_paid_to_seller' => 'float',
        'delivery_fee' => 'float',
        'cod' => 'float',
        'intended_delivery_at' => 'datetime',
        'extra_services' => 'json',
        'created_at_origin' => 'datetime',
        'inspected' => 'boolean',
        'has_document_inventory' => 'boolean',
        'packed_at' => 'datetime',
    ];

    /**
     * Shipping financial status
     */
    const SFS_INIT            = 'INIT'; // initialization
    const SFS_WAITING_COLLECT = 'WAITING_COLLECT'; // Waiting to collect money
    const SFS_RECONCILIATION  = 'RECONCILIATION'; // Financial reconciliation
    const SFS_COLLECTED       = 'COLLECTED'; // Collected money

    /**
     * Trang thái tài chính
     */
    const FINANCE_STATUS_UNPAID = 'UNPAID'; // Chưa thanh toán
    const FINANCE_STATUS_PAID   = 'PAID'; // Đã thanh toán

    const PAYMENT_TYPE_COD              = 'COD';
    const PAYMENT_TYPE_ADVANCE_PAYMENT  = 'ADVANCE_PAYMENT'; // Thanh toán trước
    const PAYMENT_TYPE_DEFERRED_PAYMENT = 'DEFERRED_PAYMENT'; // Thanh toán sau

    /**
     * @var array
     */
    public static $paymentTypes = [
        self::PAYMENT_TYPE_COD,
        self::PAYMENT_TYPE_ADVANCE_PAYMENT,
        self::PAYMENT_TYPE_DEFERRED_PAYMENT,
    ];

    /**
     * Lý do Hủy
     */
    const CANCEL_REASON_ODZ        = 'ODZ';
    const CANCEL_REASON_CUSTOMER   = 'CUSTOMER';
    const CANCEL_REASON_DUPLICATED = 'DUPLICATED';
    const CANCEL_REASON_OTHER      = 'OTHER';
    const CANCEL_REASON_SELLER     = 'SELLER';

    /**
     * @var array
     */
    public static $cancelReasons = [
        self::CANCEL_REASON_ODZ,
        self::CANCEL_REASON_CUSTOMER,
        self::CANCEL_REASON_DUPLICATED,
        self::CANCEL_REASON_OTHER,
        self::CANCEL_REASON_SELLER
    ];

    const STATUS_WAITING_INSPECTION = 'WAITING_INSPECTION'; // chờ chọn kho
    const STATUS_WAITING_CONFIRM    = 'WAITING_CONFIRM'; // chờ xác nhận
    const STATUS_WAITING_PROCESSING = 'WAITING_PROCESSING'; // chờ xử lý
    const STATUS_WAITING_PICKING    = 'WAITING_PICKING'; // chờ nhặt hàng
    const STATUS_WAITING_PACKING    = 'WAITING_PACKING'; // chờ đóng gói
    const STATUS_WAITING_DELIVERY   = 'WAITING_DELIVERY'; // Chờ giao
    const STATUS_DELIVERING         = 'DELIVERING'; //Đang giao hàng
    const STATUS_PART_DELIVERED     = 'PART_DELIVERED'; //Đã giao 1 phần hàng
    const STATUS_DELIVERED          = 'DELIVERED'; //Đã giao hàng
    const STATUS_FINISH             = 'FINISH'; //Hoàn thành
    const STATUS_CANCELED           = 'CANCELED'; // Đã hủy
    const STATUS_RETURN             = 'RETURN'; // Sẽ trả hàng hoặc đang trả hàng
    const STATUS_RETURN_COMPLETED   = 'RETURN_COMPLETED'; // Đã trả hàng
    const STATUS_FAILED_DELIVERY    = 'FAILED_DELIVERY'; // giao hàng lỗi

    static $listStatus = [
        self::STATUS_WAITING_INSPECTION,
        self::STATUS_WAITING_CONFIRM,
        self::STATUS_WAITING_PROCESSING,
        self::STATUS_WAITING_PICKING,
        self::STATUS_WAITING_PACKING,
        self::STATUS_WAITING_DELIVERY,
        self::STATUS_DELIVERING,
        self::STATUS_PART_DELIVERED,
        self::STATUS_DELIVERED,
        self::STATUS_FINISH,
        self::STATUS_CANCELED,
        self::STATUS_RETURN,
        self::STATUS_RETURN_COMPLETED,
        self::STATUS_FAILED_DELIVERY,
    ];

    /**
     * Những thông tin được phép cập nhật trên đơn
     * @var array
     */
    static $updateOrderParams = [
        'description', 'campaign', 'receiver_name', 'receiver_address', 'receiver_phone', 'receiver_note',
        'receiver_province_id', 'receiver_district_id', 'receiver_ward_id', 'receiver_postal_code'
    ];

    /**
     * @return BelongsToMany
     */
    public function skuCombos()
    {
        return $this->belongsToMany(SkuCombo::class, 'order_sku_combos', 'order_id', 'sku_combo_id');
    }

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
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
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
    public function customerAddress()
    {
        return $this->belongsTo(CustomerAddress::class, 'customer_address_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function orderSkus()
    {
        return $this->hasMany(OrderSku::class);
    }

    /**
     * @return HasMany
     */
    public function orderSkuCombos()
    {
        return $this->hasMany(OrderSkuCombo::class);
    }

    /**
     * @return BelongsTo
     */
    public function orderSkuComboSkus()
    {
        return $this->hasMany(OrderSkuComboSku::class, 'order_id', 'id');
    }

    /**
     * @return BelongsToMany
     */
    public function skus()
    {
        return $this->belongsToMany(Sku::class, 'order_skus', 'order_id', 'sku_id');
    }

    /**
     * @return HasMany
     */
    public function orderStocks()
    {
        return $this->hasMany(OrderStock::class, 'order_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function orderTransactions()
    {
        return $this->hasMany(OrderTransaction::class, 'order_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function orderImportReturnGoodsServices()
    {
        return $this->hasMany(OrderImportReturnGoodsService::class, 'order_id', 'id');
    }

    /**
     * @return BelongsToMany
     */
    public function importReturnGoodsServices()
    {
        return $this->belongsToMany(Service::class, 'order_import_return_goods_services', 'order_id', 'service_id');
    }

    /**
     * @return BelongsToMany
     */
    public function importReturnGoodsServicePrices(): BelongsToMany
    {
        return $this->belongsToMany(ServicePrice::class, 'order_import_return_goods_services', 'order_id', 'service_price_id');
    }


    /**
     * @return HasMany
     */
    public function freightBills()
    {
        return $this->hasMany(FreightBill::class)
            ->where(function ($query) {
                $query->where('status', '<>', FreightBill::STATUS_CANCELLED)
                    ->orWhereNull('status');
            })
            ->where('freight_bill_code', '<>', '');
    }

    /**
     * @return BelongsTo
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id', 'id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function receiverCountry()
    {
        return $this->belongsTo(Location::class, 'receiver_country_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function receiverProvince()
    {
        return $this->belongsTo(Location::class, 'receiver_province_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function receiverDistrict()
    {
        return $this->belongsTo(Location::class, 'receiver_district_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function receiverWard()
    {
        return $this->belongsTo(Location::class, 'receiver_ward_id', 'id');
    }

    /**
     * @return string
     */
    public function fullReceiverAddress()
    {
        $fullAddress = $this->receiver_address;
        if ($this->receiverWard) {
            $fullAddress .= ', ' . $this->receiverWard->label;
        }
        if ($this->receiverDistrict) {
            $fullAddress .= ', ' . $this->receiverDistrict->label;
        }
        if ($this->receiverProvince) {
            $fullAddress .= ', ' . $this->receiverProvince->label;
        }
        return $fullAddress;
    }

    /**
     * Get object type
     *
     * @return string
     */
    public function getObjectType()
    {
        return StockLog::OBJECT_ORDER;
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
     * @param User $user
     * @return bool`
     */
    public function canCreatePackage(User $user)
    {
        if (!$user->can(Permission::ORDER_PACKAGED)) {
            return false;
        }
        $waitingPickSkus = $this->orderStocks()
            ->whereRaw('quantity != packaged_quantity')
            ->get();

        return count($waitingPickSkus) != 0 && $this->status != self::STATUS_WAITING_INSPECTION;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function canPaymentConfirm(User $user)
    {
        if (!$user->can(Permission::ORDER_UPDATE)) {
            return false;
        }
        return ($this->getAttribute('debit_amount') > 0);
    }

    /**
     * @return BelongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id', 'id');
    }

    /**
     * @return bool
     */
    public function canUpdateOrderSKU()
    {
        return (in_array($this->getAttribute('status'), [self::STATUS_WAITING_CONFIRM, self::STATUS_WAITING_INSPECTION]));
    }

    /**
     * @return bool
     */
    public function canUpdateOrder()
    {
        return (in_array($this->getAttribute('status'), [self::STATUS_WAITING_CONFIRM, self::STATUS_WAITING_INSPECTION]));
    }

    /**
     * @return BelongsTo
     */
    public function shippingPartner()
    {
        return $this->belongsTo(ShippingPartner::class, 'shipping_partner_id');
    }

    /**
     * @return BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Set subject's place
     *
     * @return string
     */
    public function getSubjectPlace()
    {
        return $this->getAttribute('status');
    }

    /**
     * Get current subject's place
     *
     * @param string $place
     * @throws WorkflowException
     */
    public function setSubjectPlace($place)
    {
        if (!$this->update(['status' => $place])) {
            throw new WorkflowException("Update status {$place} for order {$this->getKey()} failed");
        }
    }

    /**
     * @param string $shippingFinacialStatus
     * @param User|null $user
     * @return void
     * @throws WorkflowException
     */
    public function changeShippingFinancialStatus(string $shippingFinacialStatus, User $user = null)
    {
        /** @var FreightBill $currentFreightBill */
        if ($currentFreightBill = $this->freightBills->first) {
            if ($currentFreightBill->created_at < Carbon::parse('2023-04-01 00:00:00')) {
                return;
            }
        } else {
            return;
        }
        switch ($shippingFinacialStatus) {
            case static::SFS_INIT:
                if (empty($this->shipping_financial_status)) {
                    $this->shipping_financial_status = $shippingFinacialStatus;
                }
                break;
            case static::SFS_WAITING_COLLECT:
                if ($this->shipping_financial_status == static::SFS_INIT) {
                    $this->shipping_financial_status = $shippingFinacialStatus;
                }
                break;
            case static::SFS_RECONCILIATION:
                if ($this->shipping_financial_status == static::SFS_WAITING_COLLECT) {
                    $this->shipping_financial_status = $shippingFinacialStatus;
                }
                break;
            case static::SFS_COLLECTED:
                if ($this->shipping_financial_status == static::SFS_RECONCILIATION) {
                    $this->shipping_financial_status = $shippingFinacialStatus;
                }
                break;
            default:
        }
        if ($this->isDirty('shipping_financial_status')) {
            (new OrderAttributesChanged($this, $user ?: Service::user()->getSystemUserDefault(),
                Arr::only($this->getOriginal(), ['shipping_financial_status']),
                ['shipping_financial_status' => $shippingFinacialStatus]))->queue();
            $this->save();
        } else {
            throw new WorkflowException($this->code . ' dont update shipping financial status from ' .
                $this->shipping_financial_status . ' to ' . $shippingFinacialStatus);
        }
    }

    /**
     * Thay đổi trạng thái đơn
     *
     * @param string $status
     * @param User $creator
     * @param array $payload
     * @throws WorkflowException
     */
    public function changeStatus($status, User $creator, array $payload = [])
    {
        /**
         * Do xảy ra việc đồng bộ trạng thái đơn từ các kênh bán nên sinh ra những trạng thái ngoại lệ
         * nếu luôn cho phép đi theo luồng sẽ dẫn đến những trạng thái chết (không di chuyển trạng thái được tiếp)
         */
        /**
         * Chuyển từ chờ nhặt hàng về chờ xử lý (kiểm tra lại xem mã vận đơn đã huỷ hay chưa)
         */
        switch ($status) {
            case Order::STATUS_WAITING_PROCESSING:
                $currentFreightBill = $this->freightBills()->first();
                if ($this->status == Order::STATUS_WAITING_PICKING && empty($currentFreightBill)) {
                    $orderPacking = $this->orderPacking;
                    if ($orderPacking && $orderPacking->canChangeStatus($status)) {
                        $orderPacking->changeStatus($status, $creator);
                        return;
                    }
                    Service::order()->workflow()->change($this, $status, array_merge($payload, ['creator' => $creator]));
                    return;
                }
        }

        /**
         * Trường hợp đơn đồng bộ về đang ở trạng thái chờ nhặt hàng và muốn chuyển đổi sang trạng thái Chờ đóng gói
         * nhưng đơn hàng chưa chọn được kho xuất do sản phẩm đồng bộ từ bên thứ 3 mới đồng bộ về chưa nhập kho
         * thì không cho phép chuyển đổi trạng thái
         */
        if ($this->status == self::STATUS_WAITING_PICKING && $status == self::STATUS_WAITING_PACKING && $this->inspected == false) {
            return;
        }

        Service::order()->workflow()->change($this, $status, array_merge($payload, ['creator' => $creator]));
    }

    /**
     * Kiểm tra có thể đổi trạng thái đơn hay không
     *
     * @param string $status
     * @return bool
     */
    public function canChangeStatus($status)
    {
        return Service::order()->workflow()->canChange($this, $status);
    }

    /**
     * @return HasMany
     */
    public function orderPackings()
    {
        return $this->hasMany(OrderPacking::class);
    }

    /**
     * @return HasMany
     */
    public function orderProductPriceDetails()
    {
        return $this->hasMany(OrderProductPriceDetail::class);
    }

    /**
     * @return HasOne
     */
    public function orderPacking(): HasOne
    {
        return $this->hasOne(OrderPacking::class);
    }

    /**
     * @return HasMany
     */
    public function orderExportings()
    {
        return $this->hasMany(OrderExporting::class);
    }

    /**
     * Lấy YCXH hiện hành của đơn ở 1 kho cụ thể
     * @param $warehouseId
     * @return OrderPacking|null
     */
    public function orderExporting($warehouseId)
    {
        return $this->orderExportings->where('warehouse_id', $warehouseId)->first();
    }

    /**
     * Kiểm tra đơn có thể thực hiện đồng bộ thông tin từ marketplace hay không
     *
     * @return bool
     */
    public function canSync()
    {
        return in_array($this->getAttribute('marketplace_code'), [Marketplace::CODE_SHOPEE], true);
    }

    /**
     * @return bool
     */
    public function canChangeShippingPartner()
    {
        return StatusOrder::isBeforeStatus(self::STATUS_DELIVERING, $this->getAttribute('status'));
    }

    /**
     * @return BelongsToMany
     */
    public function documents()
    {
        return $this->belongsToMany(Document::class, 'document_orders', 'order_id', 'document_id');
    }

    /**
     * @return HasMany
     */
    public function documentOrders()
    {
        return $this->hasMany(DocumentOrder::class, 'order_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function orderPackingServices()
    {
        return $this->hasMany(OrderPackingService::class, 'order_id', 'id');
    }


    /**
     * @return BelongsToMany
     */
    public function exportServices()
    {
        return $this->belongsToMany(ServiceModel::class, 'order_packing_services', 'order_id', 'service_id');
    }

    /**
     * @return BelongsToMany
     */
    public function exportServicePrices()
    {
        return $this->belongsToMany(ServicePrice::class, 'order_packing_services', 'order_id', 'service_price_id');
    }

    /**
     * @return Builder[]|Collection|\Illuminate\Support\Collection
     */
    public function extentServices()
    {
        $extentServiceIds = [];
        foreach ($this->skus as $sku) {
            $extentServiceIds = array_merge($extentServiceIds, $sku->product->services->where('type', Service\Models\Service::SERVICE_TYPE_EXTENT)->pluck('id')->all());
        }
        if ($extentServiceIds) {
            return Service\Models\Service::query()->whereIn('id', array_unique($extentServiceIds))->get();
        }
        return collect([]);
    }

    /**
     * @return HasMany
     */
    public function documentFreightBillInventories()
    {
        return $this->hasMany(DocumentFreightBillInventory::class, 'order_id', 'id');
    }


    /**
     * @return array [service_id => servicePrice]
     */
    public function extentServicePrices()
    {
        $extentServicePrices = [];
        foreach ($this->skus as $sku) {
            $extentServiceSkuIds = $sku->product->services->where('type', Service\Models\Service::SERVICE_TYPE_EXTENT)->pluck('code')->all();
            foreach ($extentServiceSkuIds as $extentServiceSkuId) {
                /** @var ServicePrice $extentServicePriceSku */
                $extentServicePriceSku = $sku->product->servicePrices->where('service_code', $extentServiceSkuId)->sortByDesc('deduct')->first();
                if (empty($extentServicePriceSku)) {
                    continue;
                }
                if (empty($extentServicePrices[$extentServiceSkuId])) {
                    $extentServicePrices[$extentServiceSkuId] = $extentServicePriceSku;
                } elseif ($extentServicePrices[$extentServiceSkuId]->deduct < $extentServicePriceSku->deduct) {
                    $extentServicePrices[$extentServiceSkuId] = $extentServicePriceSku;
                }
            }
        }

        return $extentServicePrices;
    }

    /**
     * Chi phí vận hành của đơn khi tạo đơn
     *
     * @return float
     */
    public function extentServiceAmountInit()
    {
        $extentServiceAmount = 0;
        /** @var ServicePrice $extentServicePrice */
        foreach ($this->extentServicePrices() as $extentServicePrice) {
            $extentServiceAmount += ($extentServicePrice->deduct * $this->cod) + $extentServicePrice->price;
        }
        return $extentServiceAmount;
    }

    /**
     * Cân nặng dự kiến đơn
     *
     * @return float
     */
    public function expectedWeight()
    {
        return $this->orderSkus->map(function (OrderSku $orderSku) {
            return ['weight' => $orderSku->sku->weight * $orderSku->quantity];
        })->sum('weight');
    }

    /**
     * Chi phí mở rộng của đơn khi tạo đơn
     *
     * @param $paidCod
     * @return float
     */
    public function extentServiceAmount($paidCod)
    {
        $extentServiceAmount = 0;
        /** @var ServicePrice $extentServicePrice */
        foreach ($this->extentServicePrices() as $extentServicePrice) {
            $extentServiceAmount += ($extentServicePrice->deduct * $paidCod) + $extentServicePrice->price;
        }
        return $extentServiceAmount;
    }

    /**
     * @param User $creator
     * @return OrderActivityLogger
     */
    public function activityLogger(User $creator)
    {
        return new OrderActivityLogger($this, $creator);
    }

    /**
     * @return bool
     */
    public function canRemoveWarehouseArea()
    {
        $allowStatus  = (in_array($this->getAttribute('status'), [self::STATUS_WAITING_PROCESSING, self::STATUS_WAITING_PICKING]));
        $orderPacking = $this->orderPacking;
        if ($allowStatus && $orderPacking && !$orderPacking->picking_session_id) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function canAddWarehouseArea()
    {
        $allowStatus  = (in_array($this->getAttribute('status'), [self::STATUS_WAITING_PROCESSING, self::STATUS_WAITING_PICKING]));
        $orderPacking = $this->orderPacking;
        if ($allowStatus && $orderPacking && !$orderPacking->picking_session_id) {
            return true;
        }
        return false;
    }


    /**
     * @return bool
     */
    public function canAddPriority()
    {
        return (in_array($this->getAttribute('status'), [self::STATUS_WAITING_PROCESSING, self::STATUS_WAITING_PICKING]));
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return (new GetOrderTags($this))->handle();
    }

    /**
     * Kiểm tra đơn có cần xác nhận mới đc chuyển sang chờ xử lý hay ko?
     *
     * @return bool
     */
    public function requiredConfirmBeforeProcessing()
    {
        return $this->marketplace_code == Marketplace::CODE_FOBIZ;
    }

    /**
     * @return OrderWebhookEventPublisher
     */
    public function webhook()
    {
        return $this->getCachedProperty('webhook', function () {
            return new OrderWebhookEventPublisher($this);
        });
    }

    /**
     * Lấy kho đóng hàng của đơn
     *
     * @return Warehouse|null
     */
    public function getWarehouseStock(): ?Warehouse
    {
        if ($this->orderStocks->count()) {
            return $this->orderStocks->first()->warehouse;
        }

        return $this->warehouse;
    }

    /**
     * Lấy danh sách hoàn của đơn
     *
     * @return array
     */
    public function returnedSkus()
    {
        if ($this->status != static::STATUS_RETURN_COMPLETED) {
            return [];
        }

        $orderPacking = $this->orderPacking;
        if (empty($orderPacking) || !$orderPacking->freight_bill_id) {
            return [];
        }

        /** @var ImportingBarcode|null $importingBarcode */
        $importingBarcode = ImportingBarcode::query()->where([
            'imported_type' => ImportingBarcode::IMPORTED_TYPE_RETURN_GOODS,
            'freight_bill_id' => $orderPacking->freight_bill_id
        ])->first();
        return $importingBarcode ? $importingBarcode->snapshot_skus['skus'] : [];
    }

    /**
     * Scope a query to only include users of a given type.
     *
     * @param Builder $query
     * @param mixed $type
     * @return Builder
     */
    public function scopeMerchant($query, $merchantId)
    {
        if ($merchantId) {
            return $query->where('orders.merchant_id', $merchantId);
        } else {
            return $query;
        }
    }


    /**
     * Scope a query to only include users of a given type.
     *
     * @param Builder $query
     * @param mixed $type
     * @return Builder
     */
    public function scopeCode($query, $code)
    {
        if ($code) {
            return $query->where('orders.code', $code);
        } else {
            return $query;
        }
    }

    /**
     * Scope a query to only include users of a given type.
     *
     * @param Builder $query
     * @param mixed $type
     * @return Builder
     */
    public function scopeSkuCode($query, $skuCode)
    {
        if ($skuCode) {
            return $query->join('order_skus', 'orders.id', '=', 'order_skus.order_id')
                ->join('skus', 'order_skus.sku_id', '=', 'skus.id')
                ->where('skus.code', $skuCode);
        } else {
            return $query;
        }
    }

    /**
     * Scope a query to only include users of a given type.
     *
     * @param Builder $query
     * @param mixed $type
     * @return Builder
     */
    public function scopeCreatedAt($query, $createdAt)
    {
        $createdAtFromRaw = data_get($createdAt, 'from');
        $createdAtToRaw   = data_get($createdAt, 'to');

        $createdAtFrom = Carbon::parse($createdAtFromRaw)->startOfDay();
        $createdAtTo   = Carbon::parse($createdAtToRaw)->endOfDay();
        if ($createdAtToRaw && $createdAtToRaw) {
            return $query->whereBetween('orders.created_at', [$createdAtFrom, $createdAtTo]);
        } else {
            return $query;
        }
    }

    /**
     * Scope a query to only include users of a given type.
     *
     * @param Builder $query
     * @param mixed $type
     * @return Builder
     */
    public function scopeTrackingNumber($query, $trackingNumber)
    {
        if ($trackingNumber) {
            return $query->join('freight_bills', 'orders.id', '=', 'freight_bills.order_id')
                ->where(function ($query) use ($trackingNumber) {
                    $query->where('orders.freight_bill', $trackingNumber);
                    $query->orWhere('freight_bills.freight_bill_code', $trackingNumber);
                });
        } else {
            return $query;
        }
    }

    /**
     * Scope a query to only include users of a given type.
     *
     * @param Builder $query
     * @param mixed $type
     * @return Builder
     */
    public function scopeStatus($query, $status)
    {
        if ($status) {
            return $query->where('orders.status', $status);
        } else {
            return $query;
        }
    }

    /**
     * Scope a query to only include campaign
     *
     * @param Builder $query
     * @param mixed $type
     * @return Builder
     */
    public function scopeCampaign($query, $campaign)
    {
        if ($campaign) {
            return $query->where('orders.campaign', $campaign);
        } else {
            return $query;
        }
    }

    /**
     * Scope a query to only include receiver name
     *
     * @param Builder $query
     * @param mixed $type
     * @return Builder
     */
    public function scopeReceiverName($query, $receiverName)
    {
        if ($receiverName) {
            return $query->where('orders.receiver_name', 'LIKE', "%{$receiverName}%");
        } else {
            return $query;
        }
    }

    /**
     * Scope a query to only include receiver name
     *
     * @param Builder $query
     * @param mixed $type
     * @return Builder
     */
    public function scopeReceiverPhone($query, $receiverPhone)
    {
        if ($receiverPhone) {
            return $query->where('orders.receiver_phone', 'LIKE', "%{$receiverPhone}%");
        } else {
            return $query;
        }
    }

    /**
     * Scope a query to only include payment type
     *
     * @param Builder $query
     * @param mixed $type
     * @return Builder
     */
    public function scopePaymentType($query, $paymentType)
    {
        if ($paymentType) {
            return $query->where('orders.payment_type', $paymentType);
        } else {
            return $query;
        }
    }

    /**
     * Scope a query to only include payment type
     *
     * @param Builder $query
     * @param mixed $refCode
     * @return Builder
     */
    public function scopeRefCode($query, $refCode)
    {
        if ($refCode) {
            return $query->where('orders.ref_code', $refCode);
        } else {
            return $query;
        }
    }

    /**
     * @return HasOne
     */
    public function expectedTransportingOrderSnapshot(): HasOne
    {
        return $this->hasOne(ExpectedTransportingOrderSnapshot::class);
    }

    /**
     * @return bool
     */
    public function canChangeDeliveryFee(): bool
    {
        return in_array($this->getAttribute('status'), [self::STATUS_WAITING_CONFIRM, self::STATUS_WAITING_INSPECTION]);
    }

    /**
     * @return void
     */
    public function setCostOfGoods()
    {
        $this->cost_of_goods = $this->orderSkus->sum(function (OrderSku $orderSku) {
            if ($orderSku->batchOfGood) {
                return $orderSku->batchOfGood->cost_of_goods * $orderSku->quantity;
            } else {
                return 0;
            }
        });
        $this->save();
    }
}
