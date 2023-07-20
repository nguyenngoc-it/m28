<?php

namespace Modules\Lazada\Console;

use Illuminate\Console\Command;
use Modules\Lazada\Jobs\SyncLazadaOrderJob;
use Modules\Marketplace\Services\Marketplace;
use Modules\Service;
use Modules\Store\Models\Store;
use Gobiz\Log\LogService;

class SyncLazadaOrdersCommand extends Command
{
    protected $signature = 'lazada:sync-lazada-orders {sub_hour?}';

    protected $description = 'Sync lazada orders';


    protected $api;
    protected $logger;

    /**
     * @return void
     */
    public function handle()
    {
        $this->api = Service::lazada()->api();

        $this->logger = LogService::logger('lazada-cronjobs', [
            'context' => [],
        ]);

        $subHour = $this->argument('sub_hour');

        if (!$subHour) {
            $subHour = 12;
        }

        $this->warn('Start sync lazada orders');

        $stores = $this->getStores();

        /** @var Store $store */
        foreach ($stores as $store) {
            $this->warn('Start sync lazada store '.$store->name);

            try {
                $params = [
                    'access_token' => $store->getSetting('access_token'),
                    'limit'        => 100,
                    'offset'       => 0,
                    'sub_hour'     => $subHour,
                ];

                $lazadaOrderList = $this->api->getOrderList($params)->getData('data.orders');

                // dd($lazadaOrderList);

                if ($lazadaOrderList) {
                    
                    $this->logger->info('Cron Job Lazada Shop ' . $store->name . ' - Id: ' . $store->marketplace_store_id . '  - Orders Data List', $lazadaOrderList);

                    foreach ($lazadaOrderList as $lazadaOrder) {
                        $orderId = data_get($lazadaOrder, 'order_number');
                        $orderStatus = data_get($lazadaOrder, 'statuses.0');
                        if ($orderId) {
                            $params = [
                                'order_id' => $orderId,
                                'order_status' => $orderStatus,
                            ];
                            dispatch(new SyncLazadaOrderJob($store, $params));
                        }
                    }
                }

            } catch (\Exception $exception) {
                $this->warn($store->name. ' error exception '.$exception->getMessage());
            }
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    protected function getStores()
    {
        return Store::query()->where('marketplace_code', Marketplace::CODE_LAZADA)
            ->where('status', Store::STATUS_ACTIVE)
            ->get();
    }
}
