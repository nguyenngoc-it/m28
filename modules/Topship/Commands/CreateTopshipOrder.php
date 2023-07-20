<?php

namespace Modules\Topship\Commands;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\OrderPacking\Models\OrderPackingItem;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerApi\ShippingPartnerApiException;
use Modules\ShippingPartner\Services\ShippingPartnerOrder;
use Modules\Tenant\Models\Tenant;
use Modules\Topship\Services\TopshipApiInterface;
use Psr\Log\LoggerInterface;

class CreateTopshipOrder
{
    /**
     * @var OrderPacking
     */
    protected $orderPacking;

    /**
     * @var TopshipApiInterface
     */
    protected $api;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * CreateTopshipOrder constructor
     *
     * @param OrderPacking $orderPacking
     */
    public function __construct(OrderPacking $orderPacking)
    {
        $shippingPartner = $orderPacking->shippingPartner;

        $this->orderPacking = $orderPacking;

        $this->api = Service::topship()->api($shippingPartner);

        $this->logger = LogService::logger('topship', [
            'context' => [
                'order' => $orderPacking->order->only(['id', 'code']),
                'shipping_partner' => array_merge($shippingPartner->only(['id', 'provider', 'code', 'name']), [
                    'settings' => Arr::only($shippingPartner->settings, [
                        ShippingPartner::TOPSHIP_CARRIER,
                        ShippingPartner::TOPSHIP_SHIPPING_NAME,
                    ]),
                ]),
            ],
        ]);
    }

    /**
     * @return ShippingPartnerOrder
     * @throws ShippingPartnerApiException
     */
    public function handle()
    {
        try {
            return $this->createOrder();
        } catch (RestApiException $exception) {
            throw new ShippingPartnerApiException('REQUEST_ERROR', $exception->getResponse()->getData());
        }
    }

    /**
     * @return ShippingPartnerOrder
     * @throws RestApiException
     * @throws ShippingPartnerApiException
     */
    protected function createOrder()
    {
        if (!$shippingService = $this->getShippingService()) {
            throw new ShippingPartnerApiException('CANT_GET_SHIPPING_SERVICE');
        }

        $res = $this->api->createAndConfirmOrder($this->makeOrderInput($shippingService));
        $fulfillment = Arr::first($res->getData('fulfillments') ?: []);

        $result = new ShippingPartnerOrder();
        $result->code = $res->getData('order.code');
        $result->trackingNo = $fulfillment ? Arr::get($fulfillment, 'shipping_code') : null;
        $result->fee = $shippingService['fee'];
        $result->status = $fulfillment ? Arr::get($fulfillment, 'shipping_state') : null;

        return $result;
    }

    /**
     * @return array|null
     * @throws RestApiException
     */
    protected function getShippingService()
    {
        $orderPacking = $this->orderPacking;
        $shippingPartner = $orderPacking->shippingPartner;

        $allServices = $this->getShippingServices();
        $services = Collection::make($allServices)
            ->where('carrier', $shippingPartner->getSetting(ShippingPartner::TOPSHIP_CARRIER));

        if ($service = $services->firstWhere('name', $shippingPartner->getSetting(ShippingPartner::TOPSHIP_SHIPPING_NAME))) {
            return $service;
        }

        if ($service = $services->first()) {
            return $service;
        }

        $this->logger->error('CANT_GET_SHIPPING_SERVICE', [
            'shipping_services' => $allServices,
        ]);

        return null;
    }

    /**
     * @return array
     * @throws RestApiException
     */
    protected function getShippingServices()
    {
        $orderPacking = $this->orderPacking;
        $warehouse = $orderPacking->warehouse;
        $order = $orderPacking->order;
        $weight = intval($orderPacking->getTotalWeight() * 1000); // kg => g

        return $this->api->getShippingServices([
            'pickup_address' => [
                'province' => $warehouse->province->label,
                'district' => $warehouse->district->label,
                'ward' => $warehouse->ward->label,
            ],
            'shipping_address' => [
                'province' => $order->receiverProvince->label,
                'district' => $order->receiverDistrict->label,
                'ward' => $order->receiverWard->label,
            ],
            'chargeable_weight' => $weight,
            'gross_weight' => $weight,
            'basket_value' => (int)$orderPacking->total_values,
            'cod_amount' => ($order->cod !== null) ? (int)$order->cod : (int)$order->debit_amount,
            'include_insurance' => true,
        ])->getData('services');
    }

    /**
     * @param array $shippingService
     * @return array
     */
    protected function makeOrderInput(array $shippingService)
    {
        $orderPacking = $this->orderPacking;
        $order = $orderPacking->order;
        $warehouse = $orderPacking->warehouse;
        $weight = intval($orderPacking->getTotalWeight() * 1000); // kg => g

        return [
            'external_id' => (string)$order->id,
            'external_code' => $order->merchant_id.'_'.$order->code,
            'customer_address' => [
                'full_name' => $order->receiver_name,
                'phone' => $order->receiver_phone,
                'province' => $order->receiverProvince->label,
                'district' => $order->receiverDistrict->label,
                'ward' => $order->receiverWard->label,
                'address1' => $order->receiver_address,
            ],
            'shipping_address' => [
                'full_name' => $order->receiver_name,
                'phone' => $order->receiver_phone,
                'province' => $order->receiverProvince->label,
                'district' => $order->receiverDistrict->label,
                'ward' => $order->receiverWard->label,
                'address1' => $order->receiver_address,
            ],
            'lines' => $orderPacking->orderPackingItems->map(function (OrderPackingItem $item) {
                return [
                    'product_name' => $item->sku->name,
                    'quantity' => (int)$item->quantity,
                    'list_price' => $item->price,
                    'retail_price' => $item->price,
                    'payment_price' => $item->price,
                ];
            }),
            'total_items' => (int)$orderPacking->total_quantity,
            'basket_value' => (int)$orderPacking->total_values,
            'order_discount' => (int)$order->discount_amount,
            'total_discount' => (int)$order->discount_amount,
            'total_fee' => (int)$order->shipping_amount,
            'total_amount' => (int)$order->total_amount,
            'order_note' => (string)$order->receiver_note,
            'shipping' => [
                'chargeable_weight' => $weight,
                'gross_weight' => $weight,
                'cod_amount' => ($order->cod !== null) ? (int)$order->cod : (int)$order->debit_amount,
                'include_insurance' => true,
                'shipping_service_code' => $shippingService['code'],
                'shipping_service_fee' => $shippingService['fee'],
                'shipping_note' => (string)$order->receiver_note,
                'try_on' => 'open',
                'pickup_address' => [
                    'full_name' => "{$warehouse->name} - {$warehouse->code}",
                    'phone' => $warehouse->phone ?: $warehouse->tenant->getSetting(Tenant::SETTING_PHONE),
                    'province' => $warehouse->province->label,
                    'district' => $warehouse->district->label,
                    'ward' => $warehouse->ward->label,
                    'address1' => $warehouse->address,
                ],
            ],
        ];
    }
}
