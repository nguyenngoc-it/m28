<?php

namespace Modules\Order\Commands;

use Illuminate\Support\Arr;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Models\Order;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

abstract class CreateBashOrderBase
{
    /**
     * merchant_id
     * creator_id
     * code
     * receiver_name
     * receiver_phone
     * receiver_address
     * receiver_note
     * receiver_country_id
     * receiver_province_id
     * receiver_district_id
     * receiver_ward_id
     * freight_bill
     * description
     * campaign
     * currency_id
     * shipping_partner_id
     * warehouse_id
     *
     * order_skus (sku_id, quantity, tax, price, discount_amount, order_amount, total_amount)
     * payment_amount
     *
     * @var array $input
     */
    protected $input;

    /** @var Tenant $tenant */
    protected $tenant;

    /** @var Merchant $merchant */
    protected $merchant;

    /** @var array $orderSkus */
    protected $orderSkus;

    /** @var array $orderSkuCombos */
    protected $orderSkuCombos;

    /** @var User $creator */
    protected $creator;

    /** @var ShippingPartner $shippingPartner */
    protected $shippingPartner;

    /**
     * ImportOrder constructor.
     * @param array $input
     * @param User|null $creator
     */
    public function __construct(array $input, User $creator = null)
    {
        $this->input = $input;
        $this->setMerchant();
        $this->tenant    = $this->merchant->tenant;
        $this->orderSkus = Arr::get($input, 'order_skus');
        $this->orderSkuCombos = Arr::get($input, 'order_sku_combos');
        $this->creator   = $creator ? $creator : User::find(Arr::get($input, 'creator_id'));
    }

    /**
     * @return void
     */
    private function setMerchant()
    {
        $merchantId     = Arr::get($this->input, 'merchant_id', 0);
        $this->merchant = Merchant::find($merchantId);
    }

    /**
     * @return Order
     */
    protected function makeBaseOrder()
    {
        $this->input['tenant_id'] = $this->tenant->id;
        $this->input['status']    = Order::STATUS_WAITING_INSPECTION;
        return Order::create($this->input);
    }
}
