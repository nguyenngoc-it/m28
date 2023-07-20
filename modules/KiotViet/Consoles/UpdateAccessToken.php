<?php

namespace Modules\KiotViet\Consoles;

use Gobiz\Log\LogService;
use Illuminate\Console\Command;
use Modules\KiotViet\Jobs\UpdateTokenKiotVietJob;
use Modules\Marketplace\Services\Marketplace;
use Modules\Store\Models\Store;

class UpdateAccessToken extends Command
{
    protected $signature = 'kiotviet:update_access_token';
    protected $description = 'Cron Update Access Token KiotViet Shop';

    public function handle()
    {
        $logger = LogService::logger('kiotviet-cron-job-update-access-token');
        $logger->info('kiotviet-cronjobs', ['time' => time()]);
        // Lấy danh sách các store kết nối KiotViet đang active, access token hết hạn trong vòng 6h nữa để cron update access token
        $timeExpireAt = time() + 6*60*60;
        $storeKiotViets = Store::where([
            'status'           => Store::STATUS_ACTIVE,
            'marketplace_code' => Marketplace::CODE_KIOTVIET,
        ])->where(function ($query) use ($timeExpireAt) {
            $query->where('settings->expire_at', '<', $timeExpireAt)
                  ->orWhere('settings->expire_at', null);
        })->get();

        if ($storeKiotViets) {
            foreach ($storeKiotViets as $storeKiotViet) {
                dispatch(new UpdateTokenKiotVietJob($storeKiotViet));
            }
        }
    }
}
