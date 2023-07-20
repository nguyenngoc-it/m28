<?php

namespace App\Console;

use App\Console\Commands\ImportLocationCommand;
use App\Console\Commands\ReCalculateBalanceMerchant;
use App\Console\Commands\RunningMan;
use App\Console\Commands\TestCommand;
use App\Console\Commands\UpdateLocationShippingPartnerCommand;
use Gobiz\Queue\Console\RetryJobsCommand;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use Modules\KiotViet\Consoles\SyncKiotVietOrders;
use Modules\KiotViet\Consoles\SyncKiotVietProducts as ConsolesSyncKiotVietProducts;
use Modules\KiotViet\Consoles\UpdateAccessToken as ConsoleKiotVietUpdateAccessToken;
use Modules\Location\Consoles\ImportLocationCambodia;
use Modules\Location\Consoles\ImportLocationSearch;
use Modules\Location\Consoles\SubscribeM32LocationEvent;
use Modules\Merchant\Consoles\SetStoragedAt;
use Modules\Order\Console\CalculateAmountPaidToSellerCommand;
use Modules\Order\Console\CalculateServiceAmountCommand;
use Modules\Order\Console\SubscribeFobizOrderCommand;
use Modules\Order\Console\SubscribeM32OrderCommand;
use Modules\PurchasingOrder\Consoles\SubscribeM2OrderEvent;
use Modules\PurchasingOrder\Consoles\SubscribeM6PackageEvent;
use Modules\Service\Consoles\CalculateSkuStorageFeeDaily;
use Modules\Service\Consoles\RetryMerchantTransactionDaily;
use Modules\Service\Consoles\UpdateProductServicePrice;
use Modules\ShippingPartner\Consoles\AddTempTrackings;
use Modules\Shopee\Console\InitShopeeAccessTokenCommand;
use Modules\Stock\Console\MigrateStockLogsCommand;
use Modules\SupplierTransaction\Consoles\RetrySupplierTransactionDaily;
use Modules\Tenant\Console\TestConnectionCommand;
use Modules\Tiki\Console\SyncTikiProducts;
use Modules\Tiki\Console\SyncTikiQueues;
use Modules\Tiki\Console\TikiRefreshToken;
use Modules\TikTokShop\Console\SyncTikTokShopProducts;
use Modules\TikTokShop\Console\TikTokShopRefreshToken;
use Modules\Topship\Console\RegisterTopshipWebhookCommand;
use Modules\User\Console\GetUserJWTCommand;
use Modules\Location\Consoles\UpdateLabelLocationCambodia;
use Modules\Service\Consoles\RetryTransactionStorageFeeDaily;
use Modules\Shopee\Console\SyncShopeeOrdersCommand;
use Modules\Lazada\Console\SyncLazadaOrdersCommand;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        UpdateProductServicePrice::class,
        TestCommand::class,
        \Modules\App\Console\TestConnectionCommand::class,
        TestConnectionCommand::class,
        GetUserJWTCommand::class,
        ImportLocationCommand::class,
        SubscribeM32OrderCommand::class,
        SubscribeFobizOrderCommand::class,
        CalculateServiceAmountCommand::class,
        CalculateAmountPaidToSellerCommand::class,
        SubscribeM2OrderEvent::class,
        SubscribeM6PackageEvent::class,
        ImportLocationSearch::class,
        ImportLocationCambodia::class,
        AddTempTrackings::class,
        MigrateStockLogsCommand::class,
        SetStoragedAt::class,
        RegisterTopshipWebhookCommand::class,
        RetryJobsCommand::class,
        RunningMan::class,
        InitShopeeAccessTokenCommand::class,
        ConsolesSyncKiotVietProducts::class,
        SyncKiotVietOrders::class,
        ConsoleKiotVietUpdateAccessToken::class,
        SyncTikiQueues::class,
        SyncTikiProducts::class,
        TikiRefreshToken::class,
        SyncTikTokShopProducts::class,
        TikTokShopRefreshToken::class,
        UpdateLocationShippingPartnerCommand::class,
        SubscribeM32LocationEvent::class,
        ReCalculateBalanceMerchant::class,
        UpdateLabelLocationCambodia::class,
        CalculateSkuStorageFeeDaily::class,
        RetryTransactionStorageFeeDaily::class,
        RetryMerchantTransactionDaily::class,
        RetrySupplierTransactionDaily::class,
        SyncShopeeOrdersCommand::class,
        SyncLazadaOrdersCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('service:calculate-sku-storage-fee-daily')->everyTwoHours()->runInBackground()->withoutOverlapping(300);
        $schedule->command('service:retry-transaction-storage-fee-daily')->hourly()->runInBackground()->withoutOverlapping(300);
        $schedule->command('service:retry-merchant-transaction-daily')->hourly()->runInBackground()->withoutOverlapping(300);
        $schedule->command('supplier:retry-supplier-transaction-daily')->hourly()->runInBackground()->withoutOverlapping(300);
        $schedule->command('merchant:set_storaged_at')->dailyAt('00:00')->runInBackground()->withoutOverlapping(300);
        // $schedule->command('kiotviet:sync_products')->everyFiveMinutes()->runInBackground()->evenInMaintenanceMode();// Chạy cron jobs mỗi 5ph sync products KiotViet
        // $schedule->command('kiotviet:sync_orders')->everyFiveMinutes()->runInBackground()->evenInMaintenanceMode();// Chạy cron jobs mỗi 5ph sync orders KiotViet
        // $schedule->command('kiotviet:update_access_token')->everySixHours()->runInBackground()->evenInMaintenanceMode();// Chạy cron jobs mỗi 6h update access token KiotViet
        // $schedule->command('tiki:sync_queue')->everyMinute()->runInBackground()->evenInMaintenanceMode();// Chạy cron jobs mỗi 1ph sync orders Tiki
        // $schedule->command('tiki:sync_products')->everyMinute()->runInBackground()->evenInMaintenanceMode();// Chạy cron jobs mỗi 1ph sync products Tiki
        // $schedule->command('tiki:refresh_token')->everySixHours()->runInBackground()->evenInMaintenanceMode();// Chạy cron jobs mỗi 6h update access token Tiki
        $schedule->command('tiktokshop:sync_products')->everyMinute()->runInBackground()->evenInMaintenanceMode();// Chạy cron jobs mỗi 1ph sync products TikTokShop
        $schedule->command('tiktokshop:refresh_token')->everySixHours()->runInBackground()->evenInMaintenanceMode();// Chạy cron jobs mỗi 6h update access token TikTok Shop

        // tính toán trừ tiền của merchant trong các chứng từ đã hoàn thành mà chưa được trừ tiền
        $schedule->command('re-calculate-balance-merchant --type=PACKING')->dailyAt('00:00')->runInBackground()->withoutOverlapping(300);
        $schedule->command('re-calculate-balance-merchant --type=IMPORTING')->dailyAt('00:30')->runInBackground()->withoutOverlapping(300);
        $schedule->command('re-calculate-balance-merchant --type=FREIGHT_BILL_INVENTORY')->dailyAt('01:00')->runInBackground()->withoutOverlapping(300);
        $schedule->command('re-calculate-balance-merchant --type=IMPORTING_RETURN_GOODS')->dailyAt('01:30')->runInBackground()->withoutOverlapping(300);

        // tự động đồng bộ lại đơn shopee
        $schedule->command('shopee:sync-shopee-orders')->hourly()->runInBackground()->withoutOverlapping(300);
        // tự động đồng bộ lại đơn lazada
        $schedule->command('lazada:sync-lazada-orders')->hourly()->runInBackground()->withoutOverlapping(300);

        // Đồng bộ lại stock logs
        $schedule->command('running_man SyncHistoryStockLog')->daily()->runInBackground()->withoutOverlapping(300);
    }
}
