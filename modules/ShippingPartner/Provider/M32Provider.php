<?php

namespace Modules\ShippingPartner\Provider;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Gobiz\Support\Traits\RestApiRequestTrait;
use GuzzleHttp\Client;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerApi\ShippingPartnerApiException;
use Modules\ShippingPartner\Services\ShippingPartnerApi\ShippingPartnerApiInterface;
use Modules\ShippingPartner\Services\ShippingPartnerOrder;
use Illuminate\Support\Facades\Cache;
use Modules\FreightBill\Models\FreightBill;
use Psr\SimpleCache\InvalidArgumentException;

class M32Provider implements ShippingPartnerApiInterface
{
    use RestApiRequestTrait;

    /**
     * @var Client
     */
    protected $http;

    /**
     * @var string
     */
    protected $url;

    protected $appCode = '';

    /**
     * M32Provider constructor.
     * @param $url
     * @param $appCode
     * @param $appSecret
     * @param array $options
     * @throws ShippingPartnerApiException
     * @throws InvalidArgumentException
     */
    public function __construct($url, $appCode, $appSecret, array $options = [])
    {
        $this->appCode = $appCode;
        $this->url     = $url;

        $this->logger = LogService::logger('m32-api');

        $this->http = new Client(array_merge($options, [
            'base_uri' => $url,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getAppToken($appCode, $appSecret),
                'Content-Type' => 'application/json',
            ],
        ]));
    }

    /**
     * @param $appCode
     * @param $appSecret
     * @return array|mixed
     * @throws ShippingPartnerApiException
     * @throws InvalidArgumentException
     */
    protected function getAppToken($appCode, $appSecret)
    {
        $cacheKey     = 'm32_app_token_' . $this->appCode;
        $access_token = Cache::store('redis')->get($cacheKey, '');

        if (!empty($access_token)) {
            return $access_token;
        }

        $request = [
            "code" => $appCode,
            "secret" => $appSecret,
        ];

        $res = $this->request(function () use ($request) {
            return (
            new Client(['base_uri' => $this->url,
                'headers' => [
                    'Content-Type' => 'application/json',
                ]])
            )->post('application/access-tokens', ['json' => $request]);
        });

        $access_token = $res->getData('access_token');
        $expires_in   = $res->getData('expires_in');
        if (empty($access_token) || empty($expires_in)) {
            $message = $res->getData('message');
            throw new ShippingPartnerApiException("getAppToken #{$appCode} error: " . $message);
        }

        $ex = config('gobiz.m32.app_token_expire');
        $ex = $expires_in - $ex; // đang trả về dạng phút
        Cache::store('redis')->put($cacheKey, $access_token, $ex * 60);

        return $access_token;
    }

    /**
     * @param OrderPacking $orderPacking
     * @param null $pickupType
     * @return ShippingPartnerOrder
     * @throws ShippingPartnerApiException
     */
    public function createOrder(OrderPacking $orderPacking, $pickupType = null)
    {
        $request = $this->makeOrderData($orderPacking);

        $this->logger->debug('CREATE_ORDER', $request);

        // call api to M32
        $res = Service::appLog()->logTimeExecute(function () use ($request) {
            return $this->request(function () use ($request) {
                return $this->http->post('application/orders', ['json' => $request]);
            });
        }, LogService::logger('m32-api-time'),
            ' order: ' . $orderPacking->order->code . ' - orderPacking: ' . $orderPacking->id
            . ' - shippingPartner: ' . $orderPacking->shippingPartner->code
        );

        $orderId = $res->getData('order.id');
        if (!empty($orderId)) {
            $order             = new ShippingPartnerOrder();
            $order->code       = $res->getData('order.code');
            $order->trackingNo = $res->getData('order.tracking_no');
            $order->fee        = $res->getData('order.fee');
            $order->status     = $res->getData('order.status');

            return $order;
        }

        $message = $res->getData('message');
        $this->logger->debug('Error: ' . $message, ['request' => $request, 'response' => $res->getData()]);

        throw new ShippingPartnerApiException("OrderPacking #{$orderPacking->id} error: " . $message);
    }

    /**
     * Đồng bộ vận đơn sang M32
     *
     * @param OrderPacking $orderPacking
     * @return void
     */
    public function mappingOrder(OrderPacking $orderPacking)
    {
        $request = $this->makeOrderData($orderPacking);
        $this->logger->debug('MAPPING_ORDER', $request);

        // call api to M32
        Service::appLog()->logTimeExecute(function () use ($request) {
            return $this->request(function () use ($request) {
                return $this->http->post('application/orders/mapping-tracking', ['json' => $request]);
            });
        }, LogService::logger('m32-api-time'),
            ' order: ' . $orderPacking->order->code . ' - orderPacking: ' . $orderPacking->id
            . ' - shippingPartner: ' . $orderPacking->shippingPartner->code
        );
    }

    /**
     * @param OrderPacking $orderPacking
     * @return array
     */
    protected function makeOrderData(OrderPacking $orderPacking)
    {
        $order               = $orderPacking->order;
        $shippingPartner     = $orderPacking->shippingPartner;
        $shippingCarrierCode = $shippingPartner->getSetting(ShippingPartner::SETTING_CARRIER);
        $shippingConnectCode = $shippingPartner->getSetting(ShippingPartner::SETTING_CONNECT);
        if ($orderPacking->warehouse && $orderPacking->warehouse->getSetting('m32_' . $shippingPartner->code)) {
            $shippingConnectCode = $orderPacking->warehouse->getSetting('m32_' . $shippingPartner->code);
        }
        return [
            "ref" => $order->code,
            "shipping_carrier_code" => $shippingCarrierCode,
            "shipping_connect_code" => $shippingConnectCode,
            "receiver_name" => $order->receiver_name,
            "receiver_phone" => $order->receiver_phone,
            "receiver_address" => $order->receiver_address,
            "receiver_district_code" => $order->receiverDistrict ? $order->receiverDistrict->code : '',
            "receiver_ward_code" => $order->receiverWard ? $order->receiverWard->code : '',
            "receiver_postal_code" => $order->receiver_postal_code,
            "weight" => $this->getWeight($orderPacking),
            "cod" => ($order->cod !== null) ? $order->cod : $order->debit_amount,
            'total_amount' => $order->total_amount,
            'order_amount' => $order->order_amount,
            "items" => $this->makeOrderItemData($orderPacking),
            "freight_bill_code" => $orderPacking->freightBill ? $orderPacking->freightBill->freight_bill_code : null,
        ];
    }

    /**
     * @param OrderPacking $orderPacking
     * @return float|int
     */
    protected function getWeight(OrderPacking $orderPacking)
    {
        $weight            = 0;
        $orderPackingItems = $orderPacking->orderPackingItems;
        foreach ($orderPackingItems as $orderPackingItem) {
            $weight = $weight + ($orderPackingItem->sku->weight * $orderPackingItem->quantity);
        }

        return $weight;
    }


    /**
     * @param OrderPacking $orderPacking
     * @return array
     */
    protected function makeOrderItemData(OrderPacking $orderPacking)
    {
        $orderPackingItems = $orderPacking->orderPackingItems;
        $items             = [];
        foreach ($orderPackingItems as $orderPackingItem) {
            $sku     = $orderPackingItem->sku;
            $items[] = [
                'code' => $sku->code,
                'name' => $sku->name,
                'weight' => $sku->weight,
                'price' => $orderPackingItem->price,
                'quantity' => $orderPackingItem->quantity
            ];
        }

        return $items;
    }

    /**
     * Lấy url in tem của danh sách mã vận đơn
     *
     * @param int $shippingPartnerId
     * @param array $freightBillCodes
     * @return string
     * @throws RestApiException
     */
    public function getOrderStampsUrl($shippingPartnerId, array $freightBillCodes)
    {
        $shippingPartner     = ShippingPartner::find($shippingPartnerId);
        $shippingPartnerCode = $shippingPartner->getSetting('connect_code');

        $shippingPartnerDatas = [];
        foreach ($freightBillCodes as $freightBillCode) {
            $shippingConnectCode = $shippingPartnerCode;
            $freightBill = FreightBill::where('freight_bill_code', $freightBillCode)->first();
            if ($freightBill) {
                $orderPacking = $freightBill->orderPacking;
                if ($orderPacking->warehouse && $orderPacking->warehouse->getSetting('m32_' . $shippingPartner->code)) {
                    $shippingConnectCode = $orderPacking->warehouse->getSetting('m32_' . $shippingPartner->code);
                }
            }
            $shippingPartnerDatas[$shippingConnectCode][] = $freightBillCode;
        }

        $dataReturn = [];
        if ($shippingPartnerDatas) {
            foreach ($shippingPartnerDatas as $shippingPartnerCode => $freightBillCodes) {
                $dataUrl = $this->sendRequest(function () use ($shippingPartnerCode, $freightBillCodes) {
                    return $this->http->get("application/shipping-partners/{$shippingPartnerCode}/stamps", [
                        'json' => ['tracking_nos' => $freightBillCodes],
                    ]);
                })->getData('url');
                $dataReturn[] = $dataUrl;
            }
        }

        return $dataReturn;
    }
}
