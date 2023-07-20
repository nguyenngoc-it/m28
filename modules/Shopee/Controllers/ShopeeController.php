<?php

namespace Modules\Shopee\Controllers;

use App\Base\Controller;
use Gobiz\Log\LogService;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use Modules\Marketplace\Services\Marketplace;
use Modules\Order\Models\Order;
use Modules\Shopee\Jobs\SyncShopeeFreightBillJob;
use Modules\Shopee\Jobs\SyncShopeeOrdersJob;
use Modules\Shopee\Services\Shopee;

class ShopeeController extends Controller
{
    public function webhook()
    {
        $logger = LogService::logger('shopee-events');
        $event = $this->request()->getContent();

        $logger->debug('event', json_decode($event, true));

        $key = config('services.shopee.partner_key');
        $signString = rtrim(config('app.public_url'), '/').'/webhook/shopee|'.$event;
        $expectedSign = hash_hmac('sha256', $signString, $key);
        $sign = $this->request()->header('Authorization');

        if ($sign !== $expectedSign) {
            $logger->error('UNAUTHENTICATED', [
                'sign_string' => $signString,
                'request_sign' => $sign,
                'expected_sign' => $expectedSign,
            ]);
//            throw new InvalidArgumentException("Shopee webhook authorization failed");
        }

        $shopId = $this->request()->get('shop_id');
        $data = $this->request()->get('data');

        switch ((int)$this->request()->get('code')) {
            case Shopee::WEBHOOK_ORDER_STATUS_UPDATE: {
                if ($data['status'] !== Shopee::ORDER_STATUS_UNPAID) {
                    $this->dispatch(new SyncShopeeOrdersJob($shopId, [
                        [
                            'order_sn' => $data['ordersn'],
                            'order_status' => $data['status'],
                        ],
                    ]));
                }
                return null;
            }

            case Shopee::WEBHOOK_TRACKING_NO: {
                $this->dispatch(new SyncShopeeFreightBillJob($shopId, $data['ordersn'], $data['tracking_no']));
                return null;
            }

            default:
                return null;
        }
    }

    public function syncOrders()
    {
        $shopId = (int)$this->request()->get('shop_id');
        $orderCodes = $this->request()->get('order_codes') ?: [];

        if ($shopId) {
            $orderInputs = array_map(function ($orderCode) {
                return ['order_sn' => $orderCode];
            }, $orderCodes);

            $this->dispatch(new SyncShopeeOrdersJob($shopId, $orderInputs));

            return $this->response()->success(['total' => count($orderCodes)]);
        }

        $shopOrders = Order::query()
            ->where('marketplace_code', Marketplace::CODE_SHOPEE)
            ->whereIn('code', $orderCodes)
            ->get(['id', 'marketplace_store_id', 'code'])
            ->groupBy('marketplace_store_id');

        $total = 0;
        foreach ($shopOrders as $shopId => $orders) {
            /**
             * @var Collection $orders
             */
            $orders->chunk(20)->each(function (Collection $orders) use ($shopId, &$total) {
                $orderInputs = $orders->map(function (Order $order) {
                    return ['order_sn' => $order->code];
                })->all();

                $this->dispatch(new SyncShopeeOrdersJob($shopId, $orderInputs));
                $total++;
            });
        }

        return $this->response()->success(['total' => $total]);
    }
}
