<?php

namespace Modules\Shopee\Jobs;

use App\Base\Job;
use Carbon\Carbon;
use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Arr;
use Modules\Marketplace\Services\Marketplace;
use Modules\Service;
use Modules\Store\Models\Store;

class WaitForOrderShippingPartnerJob extends Job implements ShouldBeUnique
{
    /**
     * @var string
     */
    public $queue = 'shopee-monitor';

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var string
     */
    protected $orderCode;

    /**
     * WaitForOrdersShippingPartnerJob constructor
     *
     * @param int $storeId
     * @param string $orderCode
     */
    public function __construct($storeId, $orderCode)
    {
        $this->storeId = $storeId;
        $this->orderCode = $orderCode;
        $this->delay = $this->getConfigDelay();
    }

    /**
     * @return Carbon
     */
    public function retryUntil()
    {
        return Carbon::now()->addSeconds(config('services.shopee.order_shipping_partner.sync_duration'));
    }

    /**
     * @return string
     */
    public function uniqueId()
    {
        return $this->orderCode;
    }

    /**
     * @throws RestApiException
     * @throws \Modules\Marketplace\Services\MarketplaceException
     */
    public function handle()
    {
        $logger = LogService::logger('shopee-monitor', ['context' => [
            'store_id' => $this->storeId,
            'orderCode' => $this->orderCode,
        ]]);

        // Do v1 dùng shop_id, v2 thì lại dùng store_id
        $store = Store::find($this->storeId) ?: Store::query()->firstWhere([
            'marketplace_code' => Marketplace::CODE_SHOPEE,
            'marketplace_store_id' => $this->storeId,
        ]);

        if (!$store instanceof Store) {
            $logger->error('MONITOR_ORDER_SHIPPING_PARTNER.STORE_NOT_FOUND');
            return;
        }

        if ($store->isDisconnected()) {
            $logger->error('MONITOR_ORDER_SHIPPING_PARTNER.STORE_DISCONNECTED');
            return;
        }

        $logger->debug('MONITOR_ORDER_SHIPPING_PARTNER');

        $orderLists = Service::shopee()->getOrderDetails($store, [$this->orderCode]);

        $order = Arr::first($orderLists);

        // Nếu order chưa có đvvc thì push lại job vào queue
        if (empty($order['shipping_carrier'])) {
            $this->release($this->getConfigDelay());
            return;
        }

        // Nếu order đã có đtvc thì thực hiện update đtvc & xóa job
        dispatch(new SyncOrderShippingPartnerJob($store->id, $order));
        $this->delete();
        $logger->debug('ORDER_SHIPPING_PARTNER_FOUND', Arr::only($order, ['shipping_carrier']));
    }

    /**
     * @return int
     */
    protected function getConfigDelay()
    {
        return (int)config('services.shopee.order_shipping_partner.sync_every') ?: 3600;
    }
}
