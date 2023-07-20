<?php

namespace Modules\KiotViet\Consoles;

use Gobiz\Log\LogService;
use Illuminate\Console\Command;
use Modules\KiotViet\Jobs\SyncKiotVietOrdersJob;
use Modules\Marketplace\Services\Marketplace;
use Modules\Store\Models\Store;

class SyncKiotVietOrders extends Command
{
    protected $signature = 'kiotviet:sync_orders';
    protected $description = 'Cron Danh Sách Đơn Hàng KiotViet';

    public function handle()
    {
        $logger = LogService::logger('kiotviet-cron-job-order');
        $logger->info('kiotviet-cronjobs', ['time' => time()]);
        // Lấy danh sách các store kết nối KiotViet đang active để cron dữ liệu product
        $storeKiotViets = Store::where([
            'status'           => Store::STATUS_ACTIVE,
            'marketplace_code' => Marketplace::CODE_KIOTVIET,
        ])->get();

        if ($storeKiotViets) {
            foreach ($storeKiotViets as $storeKiotViet) {
                dispatch(new SyncKiotVietOrdersJob($storeKiotViet, $storeKiotViet->merchant->id));
            }
        }
    }
}
