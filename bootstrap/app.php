<?php

use Modules\SupplierTransaction\Service\SupplierTransactionServiceProvider;

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();

$app->register(Jenssegers\Mongodb\MongodbServiceProvider::class);

$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Config Files
|--------------------------------------------------------------------------
|
| Now we will register the "app" configuration file. If the file exists in
| your configuration directory it will be loaded; otherwise, we'll load
| the default version. You may register other files below as needed.
|
*/

$app->configure('queue');
$app->configure('activity');
$app->configure('api');
$app->configure('email');
$app->configure('event');
$app->configure('filesystems');
$app->configure('gobiz');
$app->configure('jwt');
$app->configure('kafka');
$app->configure('services');
$app->configure('workflow');
$app->configure('upload');
$app->configure('paginate');
$app->configure('marketplace');
$app->configure('trustedproxy');
$app->configure('workflow');
$app->configure('bus');
$app->configure('webhook');
$app->configure('dompdf');
$app->configure('aws');

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->middleware([
    Modules\App\Middleware\TrustProxies::class,
    Modules\App\Middleware\CORS::class,
    Modules\App\Middleware\SentryContext::class,
    Modules\App\Middleware\Language::class,
    Modules\App\Middleware\Idempotency::class,
]);

$app->routeMiddleware([
    'auth' => Modules\Auth\Middleware\Authenticate::class,
    'can' => Modules\Auth\Middleware\Authorize::class,
    'can_any' => Modules\Auth\Middleware\AuthorizeAny::class,
    'product_of_seller' => \Modules\Product\Middleware\ProductOfSeller::class
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

$app->register(Illuminate\Redis\RedisServiceProvider::class);
$app->register(Tymon\JWTAuth\Providers\LumenServiceProvider::class);
$app->register(Sentry\Laravel\ServiceProvider::class);

$app->register(App\Services\Log\LogServiceProvider::class);
$app->register(App\Providers\RouteBindingServiceProvider::class);
$app->register(App\Providers\DebugServiceProvider::class);
$app->register(App\Providers\EventServiceProvider::class);
$app->register(Gobiz\Activity\ActivityServiceProvider::class);
$app->register(Gobiz\Bus\BusServiceProvider::class);
$app->register(Gobiz\Kafka\KafkaServiceProvider::class);
$app->register(Gobiz\Log\LogServiceProvider::class);
$app->register(Gobiz\Queue\QueueServiceProvider::class);
$app->register(Gobiz\Email\EmailServiceProvider::class);
$app->register(Gobiz\Event\EventServiceProvider::class);
$app->register(Gobiz\Setting\SettingServiceProvider::class);
$app->register(Gobiz\Transformer\TransformerServiceProvider::class);
$app->register(Gobiz\Workflow\WorkflowServiceProvider::class);

$app->register(Modules\App\Services\AppServiceProvider::class);
$app->register(Modules\Auth\Services\AuthServiceProvider::class);
$app->register(Modules\Tenant\Services\TenantServiceProvider::class);
$app->register(Modules\User\Services\UserServiceProvider::class);
$app->register(Modules\Order\Services\OrderServiceProvider::class);
$app->register(Modules\Product\Services\ProductServiceProvider::class);
$app->register(Modules\Product\Services\SkuServiceProvider::class);
$app->register(Modules\Stock\Services\StockServiceProvider::class);
$app->register(Modules\Order\Services\OrderServiceProvider::class);
$app->register(Modules\Warehouse\Services\WarehouseServiceProvider::class);
$app->register(Modules\Location\Services\LocationServiceProvider::class);
$app->register(Modules\ImportHistory\Services\ImportHistoryServiceProvider::class);
$app->register(Modules\Merchant\Services\MerchantServiceProvider::class);
$app->register(Modules\Currency\Services\CurrencyServiceProvider::class);
$app->register(Modules\Category\Services\CategoryServiceProvider::class);
$app->register(Modules\DeliveryNote\Services\DeliveryNoteServiceProvider::class);
$app->register(Modules\ShopBase\Services\ShopBaseServiceProvider::class);
$app->register(Modules\ShippingPartner\Services\ShippingPartnerServiceProvider::class);
$app->register(Modules\OrderPacking\Services\OrderPackingServiceProvider::class);
$app->register(Modules\OrderExporting\Services\OrderExportingServiceProvider::class);
$app->register(Modules\Document\Services\DocumentServiceProvider::class);
$app->register(Modules\Document\Services\DocumentExportingServiceProvider::class);
$app->register(Modules\Document\Services\DocumentPackingServiceProvider::class);
$app->register(Modules\Document\Services\DocumentImportingServiceProvider::class);
$app->register(Modules\Document\Services\DocumentFreightBillInventoryServiceProvider::class);
$app->register(Modules\PurchasingManager\Services\PurchasingManagerServiceProvider::class);
$app->register(Modules\PurchasingOrder\Services\PurchasingOrderServiceProvider::class);
$app->register(Modules\Marketplace\Services\MarketplaceServiceProvider::class);
$app->register(Modules\Store\Services\StoreServiceProvider::class);
$app->register(Modules\Shopee\Services\ShopeeServiceProvider::class);
$app->register(Modules\KiotViet\Services\KiotVietServiceProvider::class);
$app->register(Modules\Lazada\Services\LazadaServiceProvider::class);
$app->register(Modules\Tiki\Services\TikiServiceProvider::class);
$app->register(Modules\TikTokShop\Services\TikTokShopServiceProvider::class);
$app->register(Modules\ShopBaseUs\Services\ShopBaseUsServiceProvider::class);
$app->register(Modules\Sapo\Services\SapoServiceProvider::class);
$app->register(Modules\WarehouseStock\Services\WarehouseStockServiceProvider::class);
$app->register(Modules\FreightBill\Services\FreightBillServiceProvider::class);
$app->register(Modules\InvalidOrder\Services\InvalidOrderServiceProvider::class);
$app->register(Modules\Document\Services\DocumentSkuInventoryServiceProvider::class);
$app->register(Modules\Document\Services\DocumentDeliveryComparisonServiceProvider::class);
$app->register(Modules\PurchasingPackage\Services\PurchasingPackageServiceProvider::class);
$app->register(Modules\Transaction\Services\TransactionServiceProvider::class);
$app->register(Modules\Service\Services\ServiceServiceProvider::class);
$app->register(Modules\Topship\Services\TopshipServiceProvider::class);
$app->register(Modules\Locking\Services\LockingServiceProvider::class);
$app->register(Modules\EventBridge\Services\EventBridgeServiceProvider::class);
$app->register(Barryvdh\DomPDF\ServiceProvider::class);
$app->register(Modules\Supplier\Services\SupplierServiceProvider::class);
$app->register(SupplierTransactionServiceProvider::class);
$app->register(Modules\Document\Services\DocumentSupplierTransactionServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'Modules',
], function ($router) {
    require __DIR__.'/../routes/web.php';
    require __DIR__.'/../routes/api.php';
});

return $app;
