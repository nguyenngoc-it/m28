<?php

namespace Modules\Order\Commands;

use Illuminate\Support\Arr;
use Modules\Location\Models\Location;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Events\OrderCreated;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderSku;
use Modules\Order\Models\OrderSkuCombo;
use Modules\Order\Models\OrderSkuComboSku;
use Modules\Order\Models\OrderTransaction;
use Modules\Product\Models\SkuCombo;
use Modules\Product\Models\SkuComboSku;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Store\Models\StoreSku;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Gobiz\Log\LogService;

class CreateOrder
{
    /**
     * @var array
     */
    protected $input;

    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var Merchant
     */
    protected $merchant;


    /**
     * @var Location|null
     */
    protected $receiverProvince;

    /**
     * @var Location|null
     */
    protected $receiverDistrict;

    /**
     * @var Location|null
     */
    protected $receiverWard;

    /**
     * @var array
     */
    protected $orderSkus;

    /**
     * @var array
     */
    protected $orderSkuCombos;

    /**
     * @var User
     */
    protected $creator;

    /**
     * Tổng tiền khách phải trả
     * @var int
     */
    protected $totalAmount = 0;

    /**
     * Tổng số tiền hàng
     * @var int
     */
    protected $orderAmount = 0;


    /**
     * @var array
     */
    protected $extraServices = [];

    protected $shippingPartner;


    /**
     * ImportOrder constructor.
     * @param array $input
     */
    public function __construct(array $input)
    {
        $this->merchant         = Arr::pull($input, 'merchant');
        $this->receiverProvince = Arr::pull($input, 'receiverProvince');
        $this->receiverDistrict = Arr::pull($input, 'receiverDistrict');
        $this->receiverWard     = Arr::pull($input, 'receiverWard');
        $this->orderSkus        = Arr::pull($input, 'orderSkus');
        $this->orderSkuCombos   = Arr::pull($input, 'orderSkuCombos');
        $this->orderAmount      = Arr::pull($input, 'orderAmount');
        $this->totalAmount      = Arr::pull($input, 'totalAmount');
        $this->extraServices    = Arr::pull($input, 'extraServices');
        $this->shippingPartner  = Arr::pull($input, 'shippingPartner');

        $this->creator = Arr::pull($input, 'creator');
        $this->input   = $input;
    }


    /**
     * @return Order
     */
    public function handle()
    {
        $this->tenant = $this->merchant->tenant;
        try {
            /** @var Order $order */
            $order = DB::transaction(function () {
                $order        = $this->makeOrder();
                $this->makeOrderSkuCombos($order);
                $this->makeOrderSkus($order);
                $this->makeOrderTransaction($order);

                return $order;
            });
        } catch (\Exception $exception) {
            LogService::logger('create_order')->info('error '.$exception->getMessage(), ['code' => $this->input['code']]);
        }

        if($order instanceof Order) {
            (new OrderCreated($order))->queue();
        }

        return $order;
    }

    /**
     * @return Order|mixed
     */
    protected function makeOrder()
    {
        $this->input['payment_amount'] = (!empty($this->input['payment_amount'])) ? floatval($this->input['payment_amount']) : 0;
        $country                       = $this->merchant->getCountry();
        $receiver_country_id           = 0;
        $currency_id                   = 0;
        if ($country instanceof Location) {
            $receiver_country_id = $country->id;
            $currency_id         = $country->currency_id;
        }
        if (empty($this->input['freight_bill'])) {
            $this->input['freight_bill'] = null;
        }
        $data = array_merge($this->input, [
            'creator_id' => $this->creator->id,
            'status' => Order::STATUS_WAITING_INSPECTION,
            'merchant_id' => $this->merchant->id,
            'sale_id' => 0,
            'currency_id' => $currency_id,
            'receiver_country_id' => $receiver_country_id,
            'receiver_province_id' => isset($this->receiverProvince->id) ? $this->receiverProvince->id : 0,
            'receiver_district_id' => isset($this->receiverDistrict->id) ? $this->receiverDistrict->id : 0,
            'receiver_ward_id' => isset($this->receiverWard->id) ? $this->receiverWard->id : 0,
            'order_amount' => $this->orderAmount,
            'total_amount' => $this->totalAmount,
            'debit_amount' => 0,
            'paid_amount' => 0,
            'extra_services' => $this->extraServices,
            'shipping_partner_id' => $this->shippingPartner instanceof ShippingPartner ? $this->shippingPartner->id : null,
        ]);

        if (!isset($data['cod']) || $data['cod'] === null) {
            $data['cod'] = $this->totalAmount - $this->input['payment_amount'];
        }

        $data['intended_delivery_at'] = (isset($this->input['intended_delivery_at'])) ? Service::order()->formatDateTime($this->input['intended_delivery_at']) : null;
        $data['created_at_origin']    = (isset($this->input['created_at_origin'])) ? Service::order()->formatDateTime($this->input['created_at_origin']) : null;

        if (isset($data['payment_time'])) {
            unset($data['payment_time']);
        }

        if(empty($data['marketplace_code'])) {
            $data['marketplace_code'] = Marketplace::CODE_MANUAL;
        }

        return $this->tenant->orders()->firstOrCreate([
            'code' => trim($this->input['code']),
            'merchant_id' => $this->merchant->id
        ], $data);
    }

    /**
     * @param Order $order
     */
    protected function makeOrderSkuCombos(Order $order)
    {
        if ($this->orderSkuCombos) {
            foreach ($this->orderSkuCombos as $orderSkuCombo) {
                $itemId       = data_get($orderSkuCombo, 'id', 0);
                $itemQuantity = (int) data_get($orderSkuCombo, 'quantity');
                $itemPrice    = (double) data_get($orderSkuCombo, 'price');

                $skuCombo = SkuCombo::find($itemId);
                if ($skuCombo) {
                    OrderSkuCombo::updateOrCreate([
                        'order_id'     => $order->id,
                        'sku_combo_id' => $skuCombo->id,
                    ],[
                        'quantity' => $itemQuantity,
                        'price'    => $itemPrice
                    ]);

                    $skuComboSkus = $skuCombo->skuComboSkus;

                    foreach ($skuComboSkus as $skuComboSku) {

                        $skuComboQuantity = $skuComboSku->quantity;

                        $sku = $skuComboSku->sku;

                        $price          = (double) $sku->retail_price * $itemQuantity;
                        $quantity       = (int) $skuComboQuantity * $itemQuantity;
                        $tax            = 0;
                        $discountAmount = 0;
                        $orderAmount    = (float)$price * $quantity;
                        $totalAmount    = ($orderAmount + ($orderAmount * floatval($tax) * 0.01)) - $discountAmount;

                        $this->orderSkus[] = [
                            'sku_id' => $sku->id,
                            'quantity' => $quantity,
                            'tax' => $tax,
                            'price' => $price,
                            'discount_amount' => $discountAmount,
                            'order_amount' => $orderAmount,
                            'total_amount' => $totalAmount,
                            'from_sku_combo' => OrderSku::FROM_SKU_COMBO_TRUE
                        ];

                        $dataCreate = [
                            'order_id' => $order->id,
                            'sku_id' => $sku->id,
                            'sku_combo_id' => $skuCombo->id,
                            'quantity' => $quantity,
                            'price' => $price,
                        ];

                        OrderSkuComboSku::create($dataCreate);
                    }
                }
            }
        }
    }

    /**
     * @param Order $order
     */
    protected function makeOrderSkus(Order $order)
    {
        $orderSkus = [];

        if ($this->orderSkus) {
            foreach ($this->orderSkus as $data) {
                $skuId = $data['sku_id'];
                $fromSkuCombo = data_get($data, 'from_sku_combo', false);
                if($fromSkuCombo) {
                    $orderSkus[] = array_merge($data, [
                        'order_id' => $order->id,
                        'tenant_id' => $order->tenant_id,
                    ]);
                    continue;
                }

                if (isset($orderSkus[$skuId])) { //merge sku trùng id
                    $orderSkus[$skuId]['quantity']        += $data['quantity'];
                    $orderSkus[$skuId]['order_amount']    += $data['order_amount'];
                    $orderSkus[$skuId]['discount_amount'] += $data['discount_amount'];
                    $orderSkus[$skuId]['total_amount']    += $data['total_amount'];
                } else {
                    $orderSkus[$skuId] = array_merge($data, [
                        'order_id' => $order->id,
                        'tenant_id' => $order->tenant_id,
                    ]);
                }
            }
        }

        if ($orderSkus) {
            foreach ($orderSkus as $orderSku) {
                OrderSku::create($orderSku);
            }
        }
    }

    /**
     * @param Order $order
     * @return OrderTransaction|null
     */
    public function makeOrderTransaction(Order $order)
    {
        return (new CreateOrderTransaction($order, $this->input, $this->creator))->handle();
    }
}
