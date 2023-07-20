<?php

namespace Modules\TikTokShop\Console;

use Gobiz\Log\LogService;
use Illuminate\Console\Command;
use Modules\Marketplace\Services\Marketplace;
use Modules\Store\Models\Store;
use Modules\TikTokShop\Jobs\RefreshTokenTikTokShopJob;

class TikTokShopRefreshToken extends Command
{
    protected $signature = 'tiktokshop:refresh_token';
    protected $description = 'Cron Refresh Token TikTokShop Shop';

    public function handle()
    {
        $logger = LogService::logger('tiktokshop-cron-job-update-access-token');
        $logger->info('tiktokshop-cronjobs', ['time' => time()]);
        // Lấy danh sách các store kết nối TikTokShop đang active, access token hết hạn trong vòng 6h nữa để cron update access token
        $timeExpireAt = time() + 6*60*60;
        $storeTikTokShops = Store::where([
            'status'           => Store::STATUS_ACTIVE,
            'marketplace_code' => Marketplace::CODE_TIKTOKSHOP,
        ])->get();

        if ($storeTikTokShops) {
            foreach ($storeTikTokShops as $storeTikTokShop) {
                dispatch(new RefreshTokenTikTokShopJob($storeTikTokShop));
            }
        }
    }
}
