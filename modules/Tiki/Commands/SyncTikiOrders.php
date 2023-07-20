<?php

namespace Modules\Tiki\Commands;

use Gobiz\Log\LogService;
use Gobiz\Support\RestApiException;
use Modules\Marketplace\Services\Marketplace;
use Modules\Merchant\Models\Merchant;
use Modules\Service;
use Modules\Store\Models\Store;
use Modules\User\Models\User;
use Psr\Log\LoggerInterface;
use Carbon\Carbon;
use Modules\Tiki\Jobs\SyncTikiOrderJob;
use Modules\Tiki\Services\Tiki;

class SyncTikiOrders
{
    const PER_PAGE = 50;

    /**
     * @var int
     */
    protected $shopId;

    /**
     * @var int
     */
    protected $merchantId;

    /**
     * @var Merchant
     */
    protected $merchant;


    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected $filterUpdateTime = true;

    protected $api;

    /**
     * SyncTikiOrders constructor.
     * @param $store
     * @param User|null $creator
     * @param bool $filterUpdateTime
     */
    public function __construct(Store $store, User $creator = null, $filterUpdateTime = true)
    {
        $this->store            = $store;
        $this->api              = Service::tiki()->api();
        $this->filterUpdateTime = $filterUpdateTime;
        $this->dt               = Carbon::now();

        $this->logger = LogService::logger('tiki-sync-orders', [
            'context' => ['store_id' => $this->store->id, 'merchant_id' => $this->store->merchant_id, 'filterUpdateTime' => $filterUpdateTime],
        ]);
    }

    /**
     * @param Store $store
     * @return int
     */
    protected function getLastUpdateTime(Store $store)
    {
        $lastUpdateTime = Carbon::parse($store->getSetting(Store::SETTING_TIKI_PRODUCT_LAST_UPDATED_AT, $this->dt->toDateTimeString()));
        $maxTime        = $this->dt->subDays(15); //thời gian lọc tối đa là từ 15 ngày trước
        if ($maxTime->gte($lastUpdateTime)) {
            $lastUpdateTime = $maxTime;
        }

        return $lastUpdateTime;
    }

    /**
     * @return array
     * @throws RestApiException
     */
    public function handle()
    {
        $this->logger->info($this->store);
        if (!$this->store || $this->store->marketplace_code != Marketplace::CODE_TIKI) {
            $this->logger->info('Store invalid');
            return null;
        }

        $lastUpdateTime = $this->getLastUpdateTime($this->store);
        $orders = [];

        $this->logger->info($lastUpdateTime);

        $this->fetchData(0, $orders, $lastUpdateTime);

        $this->logger->info(json_encode($orders));
        if(empty($orders)) {
            $this->logger->info(' empty orders');
            return [];
        }
    }


    /**
     * @param int $paginationOffset
     * @param array $items
     * @param integer $lastUpdateTime
     * @throws RestApiException
     */
    protected function fetchData($paginationOffset = 0, &$items = [], $lastUpdateTime = null)
    {
        $page = $paginationOffset / self::PER_PAGE;
        $page += 1;
        $paramsRequest = [
            'limit'          => self::PER_PAGE,
            'page'           => $page,
            'filter_date_by' => 'last30days',
            'order_by'       => 'created_at|desc',
            'access_token'   => $this->store->getSetting('access_token')
        ];

        // Get Orders
        $orders    = $this->api->getOrders($paramsRequest);
        $items     = array_merge($items, $orders->getData('data', []));
        $itemTotal = $orders->getData('total', 0);
        $offset    = $paginationOffset + self::PER_PAGE;

        // Test sync Order
        // $orderId = '453011402';
        // dispatch(new SyncTikiOrderJob($this->store, ['order_id' => $orderId]));

        foreach ($items as $item) {
            //
            $orderId = data_get($item, 'id', 0);
            if ($orderId) {
                dispatch(new SyncTikiOrderJob($this->store, $orderId));
            }
        }

        if($itemTotal > $offset) {
            $this->fetchData($offset , $items, $lastUpdateTime);
        }
    }

}
