<?php

namespace Modules\Shopee\Console;

use Carbon\Carbon;
use Gobiz\Support\RestApiException;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Modules\Marketplace\Services\Marketplace;
use Modules\Service;
use Modules\Shopee\Jobs\ReSyncShopeeOrderJob;
use Modules\Shopee\Services\Shopee;
use Modules\Store\Models\Store;

class SyncShopeeOrdersCommand extends Command
{
    protected $signature = 'shopee:sync-shopee-orders {from?} {to?}';

    protected $description = 'Sync shopee orders';

    /**
     * @return void
     */
    public function handle()
    {
        $filter = $this->makeFilterTime();

        $this->warn('Start sync shopee orders '.json_encode($filter));

        $stores = $this->getStores();

        /** @var Store $store */
        foreach ($stores as $store) {
            $this->warn('Start sync shopee store '.$store->name);

            try {
                $res   = $store->shopeeApi()->getOrderList($filter);
                $error = $res->getData('error');
                if($error) {
                    $this->warn('error '.$store->name . ': '.$error);
                }
                $orderList = $res->getData('response.order_list');
                if(!empty($orderList)) {
                    foreach ($orderList as $item) {
                        $orderCode   = Arr::get($item, 'order_sn');
                        if(empty($orderCode)) continue;
                        $this->warn('start sync order '.$orderCode);
                        dispatch(new ReSyncShopeeOrderJob($store->marketplace_store_id, $item));
                    }
                }
            } catch (\Exception $exception) {
                $this->warn($store->name. ' error exception '.$exception->getMessage());
            }
        }
    }

    /**
     * @return array
     */
    protected function makeFilterTime()
    {
        $from = $this->argument('from');
        if($from) {
            $from = (new Carbon($from))->getTimestamp();
        }

        $to   = $this->argument('to');
        if($to) {
            $to = (new Carbon($to))->getTimestamp();
        }

        if(!$from || !$to) {
            $from = (new Carbon())->subHours(2)->getTimestamp();; // mặc định lọc các đơn 2h gần nhất
            $to   = (new Carbon())->getTimestamp();;
        }

        return [
            'time_range_field' => 'create_time',
            'time_from' => $from,
            'time_to' => $to,
            'page_size' => '99',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    protected function getStores()
    {
        return Store::query()->where('marketplace_code', Marketplace::CODE_SHOPEE)
            ->where('status', Store::STATUS_ACTIVE)
            ->get();
    }
}
