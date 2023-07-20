<?php

namespace Modules\ShippingPartner\Provider;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Gobiz\Support\RestApiResponse;
use Illuminate\Support\Arr;
use Modules\FreightBill\Models\FreightBill;
use Modules\Merchant\Models\Merchant;
use Modules\Order\Models\Order;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\ShippingPartner\Services\ShippingPartnerApi\ShippingPartnerApiException;
use Modules\ShippingPartner\Services\ShippingPartnerApi\ShippingPartnerApiInterface;
use Modules\ShippingPartner\Services\ShippingPartnerOrder;
use Psr\Log\LoggerInterface;

class ShopeeProvider implements ShippingPartnerApiInterface
{
    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * ShopeeProvider constructor.
     */
    public function __construct()
    {
        $this->logger = LogService::logger('shopee-provider');
    }

    /**
     * @param $pickup
     * @return array
     */
    protected function makePickupParams($pickup)
    {
        $pickupParams = [];

        if(!empty($pickup)) {
            $addressList = Arr::get($pickup, 'address_list');
            if(!empty($addressList)) {
                foreach ($addressList as $item) {
                    $addressFlag = (array)Arr::get($item, 'address_flag');

                    $pickupTimeId = '';
                    $timeSlotList = (array)Arr::get($item, 'time_slot_list');
                    foreach ($timeSlotList as $time) {
                        $pickupTimeId = Arr::get($time, 'pickup_time_id');
                        if(!empty($pickupTimeId)) {
                            break;
                        }
                    }

                    if(in_array('pickup_address', $addressFlag)) {
                        $pickupParams = [
                            'address_id' => Arr::get($item, 'address_id'),
                            'pickup_time_id' => $pickupTimeId
                        ];
                    }
                }
            }
        }

        return $pickupParams;
    }

    /**
     * @param $dropoff
     * @return array
     */
    protected function makeDropoffParams($dropoff)
    {
        $dropoffParams = [];

        if(!empty($dropoff)) {
            $branchList = Arr::get($dropoff, 'branch_list');
            if(!empty($branchList)) {
                foreach ($branchList as $item) {
                    $slug = '';
                    $slugList = (array)Arr::get($item, 'slug_list');
                    foreach ($slugList as $slugItem) {
                        $slug = Arr::get($slugItem, 'slug');
                    }

                    $dropoffParams = [
                        'branch_id' => Arr::get($item, 'branch_id'),
                        'slug' => $slug
                    ];
                }
            }
        }

        return $dropoffParams;
    }


    /**
     * @param $request
     * @param $pickupType
     * @param Order $order
     * @return array
     * @throws RestApiException
     * @throws \Modules\Marketplace\Services\MarketplaceException
     */
    public function getShippingParameter($request, $pickupType, Order $order)
    {
        $shippingParameter = $order->store->shopeeApi()->getShippingParameter($request)->getData();
        $response     = Arr::get($shippingParameter, 'response', []);
        $infoNeeded   = Arr::get($response, 'info_needed', []);

        $pickup        = Arr::get($response, 'pickup', []);
        $dropoff       = Arr::get($response, 'dropoff', []);

        $pickupParams  = $this->makePickupParams($pickup);
        $dropoffParams = $this->makeDropoffParams($dropoff);
        $nonIntegratedParams = Arr::get($infoNeeded, 'non_integrated', []);

        $params = [];
        if($pickupType == ShippingPartner::PICKUP_TYPE_DROPOFF) {
            $params['dropoff'] = $dropoffParams;
            $merchant = $order->merchant;
            $params['dropoff']['sender_real_name'] = ($merchant instanceof Merchant) ? $merchant->name : 'sender_real_name_default';

        } else {
            $params['pickup'] = $pickupParams;
        }

        if(!empty($nonIntegratedParams)) {
            $params['non_integrated'] = $nonIntegratedParams;
        }


        $this->logger->debug('get Logistic Info', ['params' => $params, 'pickup_type' => $pickupType, 'request' => $request]);

        return $params;
    }

    /**
     * @param OrderPacking $orderPacking
     * @param null $pickupType
     * @return ShippingPartnerOrder|void
     * @throws ShippingPartnerApiException
     */
    public function createOrder(OrderPacking $orderPacking, $pickupType = null)
    {
        $order   = $orderPacking->order;
        $request = $this->makeOrderData($order);

        $this->logger->debug('CREATE_ORDER', $request);

        // call api to shopee
        /** @var RestApiResponse $res */
        try {
            $res = Service::appLog()->logTimeExecute(function () use (&$request, $pickupType, $order) {
                $logisticParameter = $this->getShippingParameter($request, $pickupType, $order);
                $request = array_merge($request, $logisticParameter);
                return $order->store->shopeeApi()->shipOrder($request);
            }, LogService::logger('shopee-api-time'),
                ' order: ' . $order->code . ' - orderPacking: ' . $orderPacking->id
                . ' - shippingPartner: ' . $orderPacking->shippingPartner->code
            );

            $this->logger->debug('debug', ['request' => $request, 'response' => $res->getData()]);
            $error   = $res->getData('error');
            $message = $res->getData('message');
        } catch (\Exception $exception) {
            $error   = true;
            $message = $exception->getMessage();
            $this->logger->debug('error ', ['request' => $request, 'error' => $message]);
        }

        if($error) {
            throw new ShippingPartnerApiException("OrderPacking #{$orderPacking->id} error: " . $message);
        }

        return;
    }

    /**
     * @param Order $order
     * @return array
     */
    protected function makeOrderData(Order $order)
    {
        return [
            'order_sn' => $order->code
        ];


    }

    /**
     * Lấy url in tem của danh sách mã vận đơn
     * @param int $shippingPartnerId
     * @param array $freightBillCodes
     * @return array|string
     * @throws RestApiException
     */
    public function getOrderStampsUrl($shippingPartnerId, array $freightBillCodes)
    {
        return Service::shopee()->downloadShippingDocument($shippingPartnerId, $freightBillCodes);
    }

    /**
     * Đồng bộ vận đơn sang M32
     *
     * @param OrderPacking $orderPacking
     * @return void
     */
    public function mappingOrder(OrderPacking $orderPacking)
    {
        // TODO: Implement mappingOrder() method.
    }
}
