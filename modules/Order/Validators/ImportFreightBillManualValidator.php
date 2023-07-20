<?php /** @noinspection PhpReturnDocTypeMismatchInspection */

/** @noinspection PhpDocSignatureInspection */

namespace Modules\Order\Validators;

use App\Base\Validator;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Models\Order;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;

class ImportFreightBillManualValidator extends Validator
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

    /**
     * @var array
     */
    protected $insertedOrderPackings = [];

    /**
     * ImportFreightBillValidator constructor.
     * @param User $user
     * @param array $input
     * @param array $insertedOrderPackings
     */
    public function __construct(User $user, array $input, array $insertedOrderPackings = [])
    {
        $this->user                  = $user;
        $this->insertedOrderPackings = $insertedOrderPackings;
        parent::__construct($input);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'order_code' => 'required|exists:orders,code',
            'freight_bill' => 'required',
            'shipping_partner_code' => 'exists:shipping_partners,code',
            'freight_bill_status' => 'in:' . implode(',', FreightBill::$freightBillStatus),
            'receiver_phone' => '',
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
        $receiverPhone       = $this->input('receiver_phone');
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

        if ($receiverPhone && ($this->order->receiver_phone != $receiverPhone)) {
            $this->errors()->add('receiver_phone', 'not_valid_with_order');
            return;
        }

        /**
         * Không có YCDH nào
         */
        if (!$this->orderPacking = $this->getOrderPackingFromOrder()) {
            $this->errors()->add('order_packing', static::ERROR_NOT_EXIST);
            return;
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

        if (!$this->shippingPartner = ShippingPartner::query()->where('code', $shippingPartnerCode)->where('tenant_id', $this->user->tenant_id)->first()) {
            $this->errors()->add('shipping_partner_code', static::ERROR_NOT_EXIST);
            return;
        }
    }

    /**
     * @return Order
     */
    public function getOrder(): Order
    {
        return $this->order;
    }

    /**
     * @return ShippingPartner
     */
    public function getShippingPartner(): ShippingPartner
    {
        return $this->shippingPartner;
    }

    /**
     * @param array $status
     * @return OrderPacking|null|mixed
     */
    private function getOrderPackingFromOrder(array $status = [])
    {
        $query = OrderPacking::query()->where([
            'order_id' => $this->order->id
        ]);

        if ($status) {
            $query->whereIn('status', $status);
        }

        return $query->first();
    }
}
