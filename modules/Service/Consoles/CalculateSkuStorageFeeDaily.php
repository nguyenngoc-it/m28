<?php

namespace Modules\Service\Consoles;

use Carbon\Carbon;
use Gobiz\Log\LogService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Modules\Location\Models\Location;
use Modules\Merchant\Models\Merchant;
use Modules\Service\Jobs\CalculateSellerStorageFeeDailyJob;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantSetting;

class CalculateSkuStorageFeeDaily extends Command
{
    protected $signature = 'service:calculate-sku-storage-fee-daily
        {--tenant=all}{--country=all}';

    protected $description = 'Calculate storage fee for skus';

    public function handle()
    {
        $tenant  = $this->option('tenant');
        $country = $this->option('country');
        $query   = Merchant::query()->where('user_id', '>', 0);
        $this->info('Start');

        if ($tenant != 'all') {
            /** @var Tenant|null $tenant */
            $tenant = Tenant::query()->where('code', $tenant)->first();
            if ($tenant) {
                $query->where('tenant_id', $tenant->id);
            }
        }

        if ($country) {
            /** @var Location|null $country */
            $country = Location::query()->where('code', $country)->where('type', Location::TYPE_COUNTRY)
                ->where('active', true)->first();
            if ($country) {
                $query->where('location_id', $country->id);
            }
        }

        $query->chunkById(100, function (Collection $merchants) {
            /** @var Merchant $merchant */
            foreach ($merchants as $merchant) {
                $this->storageFeeSkuStatistic($merchant);
                $this->info('update stock for ' . $merchant->username);

            }
        }, 'id');

        $this->info('Done!');
    }

    /**
     * @param Merchant $merchant
     */
    protected function storageFeeSkuStatistic(Merchant $merchant)
    {
        $storageFeeClosingTime = TenantSetting::query()->where([
            'key' => Tenant::SETTING_STORAGE_FEE_CLOSING_TIME,
            'tenant_id' => $merchant->tenant_id
        ])->first();
        if (empty($storageFeeClosingTime) || empty($storageFeeClosingTime->value[$merchant->location_id])) {
            LogService::logger('sku-storage-fee-daily')
                ->error('not found closing time merchant ' . $merchant->username);
            return;
        }
        $storageFeeClosingTime = $storageFeeClosingTime->value[$merchant->location_id];

        /**
         * Nếu đã tính tiền lưu kho của Seller thì không tính toán lại trong closingTime đó nữa
         */
        $closingTime                 = $this->detectClosingTimeTarget($storageFeeClosingTime);
        $storageFeeMerchantStatistic = $merchant->storageFeeMerchantStatistics()->where('closing_time', $closingTime)->first();
        if ($storageFeeMerchantStatistic) {
            LogService::logger('sku-storage-fee-daily')
                ->error('Today, merchant has been charged ' . $merchant->username);
            return;
        }

        dispatch(new CalculateSellerStorageFeeDailyJob($merchant, $closingTime));
    }

    /**
     * @param string $storageFeeClosingTime
     * @return Carbon
     */
    protected function detectClosingTimeTarget(string $storageFeeClosingTime)
    {
        $closingTimeToday = Carbon::parse(Carbon::now()->format('Y-m-d') . ' ' . $storageFeeClosingTime);
        $beginToday       = Carbon::parse(Carbon::now()->format('Y-m-d') . ' 00:00:00');
        if (Carbon::now()->between($beginToday, $closingTimeToday)) {
            return $closingTimeToday->subDay();
        } else {
            return $closingTimeToday;
        }
    }

}
