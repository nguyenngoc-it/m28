<?php

namespace Modules\Order\Validators;

use App\Base\Validator;
use Modules\FreightBill\Models\FreightBill;
use Modules\Location\Models\Location;
use Modules\Order\Models\Order;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;

class ImportFreightBillValidator extends Validator
{
    /**
     * @var Order
     */
    protected $order;
    /** @var string */
    protected $freightBill;
    /**
     * @var ShippingPartner
     */
    protected $shippingPartner;
    /** @var OrderPacking $orderPacking */
    protected $orderPacking;
    /** @var Warehouse */
    protected $warehouse;

    /**
     * @var array
     */
    protected $insertedOrderPackings = [];

    /**
     * ImportFreightBillValidator constructor.
     * @param User $user
     * @param Warehouse $warehouse
     * @param array $input
     * @param array $insertedOrderCode
     */
    public function __construct(User $user, Warehouse $warehouse, array $input, $insertedOrderCode = [])
    {
        $this->warehouse             = $warehouse;
        $this->user                  = $user;
        $this->insertedOrderPackings = $insertedOrderCode;
        parent::__construct($input);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'order_code' => 'required|exists:orders,code',
            'freight_bill' => 'required',
            'shipping_partner_code' => 'exists:shipping_partners,code',
        ];
    }

    /**
     * @return OrderPacking
     */
    public function getOrderPacking(): OrderPacking
    {
        return $this->orderPacking;
    }

    /**
     * @return string
     */
    public function getFreightBill(): string
    {
        return $this->freightBill;
    }

    protected function customValidate()
    {
        $this->freightBill   = $this->input('freight_bill');
        $shippingPartnerCode = $this->input('shipping_partner_code');
        $orderCode           = $this->input('order_code');

        if (!$this->order = Order::query()->where([
            'code' => $orderCode,
            'tenant_id' => $this->user->tenant_id
        ])->first()) {
            $this->errors()->add('order_code', static::ERROR_NOT_EXIST);
            return;
        }

        if (empty($this->freightBill)) {
            $this->errors()->add('freight_bill', static::ERROR_EMPTY);
            return;
        }

        /**
         * YCDH đã xử lý có mvd hoặc YCDH đã xử lý nhưng không có mvd
         */
        $finishedOrderPacking = $this->getOrderPackingFromOrder([OrderPacking::STATUS_PACKED]);
        if ($finishedOrderPacking && $finishedOrderPacking->freightBills->where('freight_bill_code', $this->freightBill)->count() == 0) {
            $this->errors()->add('order_packing', 'finished_without_freight_bill');
            return;
        }
        if ($finishedOrderPacking && $finishedOrderPacking->freightBills->where('freight_bill_code', $this->freightBill)->count()) {
            $this->errors()->add('order_packing', 'finished_with_freight_bill');
            return;
        }

        /**
         * Không có YCDH nào
         */
        if (!$this->orderPacking = $this->getOrderPackingFromOrder([OrderPacking::STATUS_WAITING_PROCESSING, OrderPacking::STATUS_WAITING_PICKING, OrderPacking::STATUS_WAITING_PACKING])) {
            $this->errors()->add('order_packing', static::ERROR_NOT_EXIST);
            return;
        }

        /**
         * Chưa có mã bưu chính thì hiển thị bên tab lỗi ( chỉ áp dụng đối với thị trường Malaysia )
         */
        $shippingPartner = $this->orderPacking->shippingPartner;
        if ($shippingPartner->code == ShippingPartner::SHIPPING_PARTNER_JNTM) {
            if (!$this->order->receiver_postal_code) {
                $this->orderPacking->error_type = OrderPacking::ERROR_RECEIVER_POSTAL_CODE;
                $this->orderPacking->save();
                $this->errors()->add('order_receiver_postal_code', static::ERROR_INVALID);
                return;
            }
        }

        /**
         * Chưa có DVVC của YCDH và cũng chưa nhập DVVC
         */
        if (empty($shippingPartnerCode)) {
            if (empty($this->orderPacking->shippingPartner)) {
                $this->errors()->add('shipping_partner_code', static::ERROR_REQUIRED);
                return;
            }
            $shippingPartnerCode = $this->orderPacking->shippingPartner->code;
        }

        /**
         * YCDH lặp lại mà cùng đơn vị vận chuyển
         */
        if (in_array($this->input['order_code'], array_keys($this->insertedOrderPackings))
            && in_array($shippingPartnerCode, $this->insertedOrderPackings[$orderCode]['shipping_partner_code'])) {
            $this->errors()->add('order_packing', static::ERROR_DUPLICATED);
            return;
        }

        if (!$this->user->merchants()->find($this->order->merchant_id)) {
            $this->errors()->add('user_merchant', static::ERROR_INVALID);
            return;
        }

        if (!$this->shippingPartner = ShippingPartner::query()->where('code', $shippingPartnerCode)->where('tenant_id', $this->user->tenant_id)->first()) {
            $this->errors()->add('shipping_partner_code', static::ERROR_NOT_EXIST);
            return;
        }

        $existFreightBill = FreightBill::query()->where([
            'freight_bill_code' => $this->freightBill,
            'shipping_partner_id' => $this->shippingPartner->id,
        ])->where('status', '<>', FreightBill::STATUS_CANCELLED)->first();
        if ($existFreightBill) {
            $this->errors()->add('freight_bill', static::ERROR_ALREADY_EXIST);
            return;
        }
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return ShippingPartner
     */
    public function getShippingPartner()
    {
        return $this->shippingPartner;
    }

    /**
     * @param array $status
     * @return mixed|OrderPacking|null
     */
    private function getOrderPackingFromOrder($status = [])
    {
        $query = OrderPacking::query()->where([
            'order_id' => $this->order->id,
            'warehouse_id' => $this->warehouse->id
        ]);

        if ($status) {
            $query->whereIn('status', $status);
        }

        return $query->first();
    }
}
