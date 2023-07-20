<?php

namespace Modules\Tiki\Console;

use Gobiz\Log\LogService;
use Illuminate\Console\Command;
use Modules\Marketplace\Services\Marketplace;
use Modules\Store\Models\Store;
use Modules\Tiki\Jobs\SyncTikiProductsJob;

class SyncTikiProducts extends Command
{
    protected $signature = 'tiki:sync_products';
    protected $description = 'Cron Danh Sách Sản Phẩm Tiki';

    public function handle()
    {
        $logger = LogService::logger('tiki-cron-job-product');
        $logger->info('tiki-cronjobs', ['time' => time()]);
        // Lấy danh sách các store kết nối Tiki đang active để cron dữ liệu product
        $storeTikis = Store::where([
            'status'           => Store::STATUS_ACTIVE,
            'marketplace_code' => Marketplace::CODE_TIKI,
        ])->get();

        if ($storeTikis) {
            foreach ($storeTikis as $storeTiki) {
                dispatch(new SyncTikiProductsJob($storeTiki, $storeTiki->merchant->id));
            }
        }
    }
}
