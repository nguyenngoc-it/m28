<?php

namespace Modules\ShopBase\Jobs;

use App\Base\Job;
use Illuminate\Support\Arr;
use Modules\Order\Commands\CreateOrder;
use Modules\Order\Models\Order;
use Modules\Order\Validators\CreateOrderValidator;
use Modules\Service;
use Modules\ShopBase\Models\ShopBase;
use Modules\ShopBase\Validators\CreateOrderShopBaseValidator;

class CreateOrderJob extends Job
{
    public $connection = 'redis';

    public $queue = 'shopbase_event';

    /**
     * @var integer
     */
    protected $shopBaseId;

    /**
     * @var array
     */
    protected $shopBaseData = [];

    /**
     * CreateOrderJob constructor.
     * @param $shopBaseId
     */
    public function __construct($shopBaseId)
    {
        $this->shopBaseId = $shopBaseId;
    }

    public function handle()
    {
        $shopBase = ShopBase::find($this->shopBaseId);
        if(!$shopBase instanceof ShopBase) return;

        $merchant = $shopBase->merchant;

        $this->shopBaseData = @json_decode($shopBase->data, true);

        $validator = (new CreateOrderShopBaseValidator($merchant, $this->shopBaseData));
        if ($validator->fails()) {
            $shopBase->update(['errors' => json_encode(['validate' => $validator->errors()])]);
            return;
        }

        $user  = Service::user()->getUserShopBase();
        $user->tenant_id = $merchant->tenant_id;

        $data  = $this->makeData();
        $data  = $this->makeReceiver($validator, $data);
        $data['merchant_id'] = $merchant->id;

        $validator = (new CreateOrderValidator($merchant->tenant, $data));
        if ($validator->fails()) {
            $shopBase->update(['errors' => json_encode(['validate' => $validator->errors()])]);
            return;
        }

        $order = (new CreateOrder(array_merge($data, [
            'creator' => $user,
            'merchant' => $validator->getMerchant(),
            'orderSkus' => $validator->getOrderSkus(),
            'receiverCountry' => $validator->getReceiverCountry(),
            'receiverProvince' => $validator->getReceiverProvince(),
            'receiverDistrict' => $validator->getReceiverDistrict(),
            'receiverWard' => $validator->getReceiverWard(),
            'orderAmount' => $validator->getOrderAmount(),
            'totalAmount' => $validator->getTotalAmount(),
            'payment_amount' => $validator->getTotalAmount(),
            'extraServices' => $validator->getExtraServices(),
        ])))->handle();

        $shopBase->status = true;
        $shopBase->order_id = $order->id;
        $shopBase->save();
    }

    /**
     * @param CreateOrderShopBaseValidator $validator
     * @param $data
     * @return mixed
     */
    protected function makeReceiver($validator, $data)
    {
        $shippingAddress = Arr::get($this->shopBaseData, 'shipping_address', []);
        $province = $validator->getProvince();
        $data['receiver_province_id'] = ($province) ? $province->id : 0;
        $data['receiver_address'] = $validator->getReceiverAddress();
        $data['receiver_name'] = Arr::get($shippingAddress, 'name', '');
        $data['receiver_phone'] = Arr::get($shippingAddress, 'phone', '');

        return $data;
    }

    /**
     * @return array
     */
    protected function makeOrderSkus()
    {
        $lineItems = Arr::get($this->shopBaseData, 'line_items', []);
        $orderSkus = [];
        foreach ($lineItems as $item) {
            $item      = (array)$item;
            $sku       = Arr::get($item, 'sku', '');
            $quantity  = Arr::get($item, 'quantity', 0);
            $price     = Arr::get($item, 'price', 0);

            if(empty($sku) || empty($quantity)) continue;
            $orderSkus[] = [
                'code' => $sku,
                'discount_amount' => floatval(Arr::get($item, 'discount_amount', 0)),
                'tax' => floatval(Arr::get($item, 'tax_amount', 0)),
                'price' => floatval($price),
                'quantity' => intval($quantity)
            ];
        }

        return $orderSkus;
    }

    /**
     * @return float|int
     */
    protected function makeShippingAmount()
    {
        $shippingLines = Arr::get($this->shopBaseData, 'shipping_lines', []);
        $shippingAmount = 0;
        $discountedPrice = 0;
        foreach ($shippingLines as $shippingLine) {
            $shippingAmount += floatval(Arr::get($shippingLine, 'price', 0));
            $discountedPrice += floatval(Arr::get($shippingLine, 'discounted_price', 0));
        }

        return compact('shippingAmount', 'discountedPrice');
    }

    /**
     * @return array
     */
    protected function makeData()
    {
        $shippingAmount = $this->makeShippingAmount();
        return [
            'code' => Arr::get($this->shopBaseData, 'id', ''),
            'orderSkus'    => $this->makeOrderSkus(),
            'shipping_amount' => $shippingAmount['shippingAmount'],
            'discount_amount' => $shippingAmount['discountedPrice'],
            'created_at_origin' => Arr::get($this->shopBaseData, 'created_at', ''),
            'payment_type' => Order::PAYMENT_TYPE_ADVANCE_PAYMENT
        ];
    }
}
