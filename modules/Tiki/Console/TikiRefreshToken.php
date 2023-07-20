<?php

namespace Modules\Tiki\Console;

use Gobiz\Log\LogService;
use Illuminate\Console\Command;
use Modules\Marketplace\Services\Marketplace;
use Modules\Store\Models\Store;
use Modules\Tiki\Jobs\RefreshTokenTikiJob;

class TikiRefreshToken extends Command
{
    protected $signature = 'tiki:refresh_token';
    protected $description = 'Cron Refresh Token Tiki Shop';

    public function handle()
    {
        $logger = LogService::logger('tiki-cron-job-update-access-token');
        $logger->info('tiki-cronjobs', ['time' => time()]);
        // Lấy danh sách các store kết nối Tiki đang active, access token hết hạn trong vòng 6h nữa để cron update access token
        $timeExpireAt = time() + 6*60*60;
        $storeTikis = Store::where([
            'status'           => Store::STATUS_ACTIVE,
            'marketplace_code' => Marketplace::CODE_TIKI,
        ])->where(function ($query) use ($timeExpireAt) {
            $query->where('settings->expire_at', '<', $timeExpireAt)
                  ->orWhere('settings->expire_at', null);
        })->get();

        if ($storeTikis) {
            foreach ($storeTikis as $storeTiki) {
                dispatch(new RefreshTokenTikiJob($storeTiki));
            }
        }
    }
}
