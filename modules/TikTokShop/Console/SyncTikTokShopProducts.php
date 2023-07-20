<?php

namespace Modules\TikTokShop\Console;

use Gobiz\Log\LogService;
use Illuminate\Console\Command;
use Modules\Marketplace\Services\Marketplace;
use Modules\Store\Models\Store;
use Modules\TikTokShop\Jobs\SyncTikTokShopProductsJob;

class SyncTikTokShopProducts extends Command
{
    protected $signature = 'tiktokshop:sync_products';
    protected $description = 'Cron Danh Sách Sản Phẩm TikTokShop';

    public function handle()
    {
        $logger = LogService::logger('tiktokshop-cron-job-product');
        // Lấy danh sách các store kết nối TikTokShop đang active để cron dữ liệu product
        $storeTikTokShops = Store::where([
            'status'           => Store::STATUS_ACTIVE,
            'marketplace_code' => Marketplace::CODE_TIKTOKSHOP,
        ])->get();

        $logger->info('tiktokshop-cronjobs', ['stores' => $storeTikTokShops->toArray()]);

        if ($storeTikTokShops) {
            foreach ($storeTikTokShops as $storeTikTokShop) {
                dispatch(new SyncTikTokShopProductsJob($storeTikTokShop, $storeTikTokShop->merchant->id, true));
            }
        }
    }
}
