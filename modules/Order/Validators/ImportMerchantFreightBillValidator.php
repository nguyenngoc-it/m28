<?php

namespace Modules\Order\Validators;

use App\Base\Validator;
use Modules\FreightBill\Models\FreightBill;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Models\Order;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;

class ImportMerchantFreightBillValidator extends Validator
{
    /** @var Order $order */
    protected $order;
    /** @var Merchant $merchant */
    protected $merchant;
    /** @var string */
    protected $freightBill;
    /** @var ShippingPartner $shippingPartner */
    protected $shippingPartner;
    /** @var OrderPacking $orderPacking */
    protected $orderPacking;

    /**
     * @var array
     */
    protected $insertedOrderPackings = [];

    /**
     * ImportFreightBillValidator constructor.
     * @param Merchant $merchant
     * @param array $input
     * @param User $user
     * @param array $insertedOrderPackings
     */
    public function __construct(Merchant $merchant, array $input, User $user, $insertedOrderPackings = [])
    {
        $this->merchant              = $merchant;
        $this->user                  = $user;
        $this->insertedOrderPackings = $insertedOrderPackings;
        parent::__construct($input);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'order_code' => 'required',
            'freight_bill' => 'required',
            'shipping_partner_code' => 'required',
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

    protected function customValidate()
    {
        $this->freightBill   = $this->input('freight_bill');
        $shippingPartnerCode = $this->input('shipping_partner_code');
        $orderCode           = $this->input('order_code');
        if (!$this->order = Order::query()->where([
            'code' => $orderCode,
            'tenant_id' => $this->user->tenant_id,
            'merchant_id' => $this->merchant->id
        ])->first()) {
            $this->errors()->add('order_code', static::ERROR_NOT_EXIST);
            return;
        }
        if (empty($this->freightBill) || empty($shippingPartnerCode)) {
            $this->errors()->add('shipping_partner', static::ERROR_REQUIRED);
            return;
        }

        if (!$this->shippingPartner = ShippingPartner::query()->where('code', $shippingPartnerCode)->where('tenant_id', $this->user->tenant_id)->first()) {
            if (!$this->shippingPartner = $this->merchant->location->shippingPartners()->whereJsonContains('shipping_partners.alias', strtolower($this->input['shipping_partner_code']))->first()) {
                $this->errors()->add('shipping_partner_code', static::ERROR_NOT_EXIST);
                return;
            };
        }

        /**
         * Không có YCDH nào ở trạng thái phù hợp
         */
        if (!$this->orderPacking = $this->getOrderPackingFromOrder([OrderPacking::STATUS_WAITING_PROCESSING, OrderPacking::STATUS_WAITING_PICKING])) {
            $this->errors()->add('order_packing', static::ERROR_EXISTS_OR_INVALID);
            return;
        }

        /**
         * YCDH lặp lại mà cùng đơn vị vận chuyển
         */
        if (in_array($this->input['order_code'], array_keys($this->insertedOrderPackings))
            && in_array($shippingPartnerCode, $this->insertedOrderPackings[$orderCode]['shipping_partner_code'])) {
            $this->errors()->add('order_packing', static::ERROR_DUPLICATED);
            return;
        }

        $existFreightBill = FreightBill::query()->where([
            'freight_bill_code' => $this->freightBill,
            'shipping_partner_id' => $this->shippingPartner->id
        ])->first();
        if ($existFreightBill) {
            $this->errors()->add('freight_bill', static::ERROR_ALREADY_EXIST);
            return;
        }
    }

    /**
     * @param array $status
     * @return mixed|OrderPacking|null
     */
    private function getOrderPackingFromOrder($status = [])
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
