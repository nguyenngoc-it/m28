<?php
/** @var Router $router */

use Laravel\Lumen\Routing\Router;
use Modules\Auth\Services\Permission;
use Modules\Merchant\Middleware\AuthenticateMerchant;

$router->options('{any:.*}', function () {
    return '';
});

$router->get('/', function () use ($router) {
    return response()->json(['service' => 'm28']);
});

$router->get('metrics', 'App\Controllers\AppController@metrics');
$router->get('login', 'Auth\Controllers\AuthController@login');
$router->get('login/callback', 'Auth\Controllers\AuthController@loginCallback');
$router->get('marketplaces/{code}/oauth-callback', 'Marketplace\Controllers\MarketplaceController@oauthCallback');

$router->group([
    'prefix' => 'tenants',
    'namespace' => 'Tenant\Controllers',
], function () use ($router) {
    $router->get('/{code}/images', 'TenantSettingController@getImages');
});

/**
 * public routes
 */
$router->group([
    'prefix' => 'public',
], function () use ($router) {
    $router->group([
        'prefix' => 'services/',
        'namespace' => 'Service\Controllers',
    ], function () use ($router) {
        $router->get('/', 'PublicServiceController@index');
        $router->post('/estimate-fee', 'PublicServiceController@estimateFee');
    });
});

/**
 * Internal routes
 */
$router->group([
    'prefix' => 'internal',
    'middleware' => [
        'auth',
        'can:' . Permission::INTERNAL_API_CALL
    ]

], function () use ($router) {
    $router->group(
        [
            'prefix' => 'tools',
            'namespace' => 'Tools\Controllers',
        ], function () use ($router) {
        $router->get('testing-double-request', 'TestingController@checkDoubleRequest');
        $router->post('call-multi-request', 'TestingController@callMultiRequest');
    });

    /**
     * tools api
     */
    $router->group([
        'middleware' => [
            'auth',
            'can:' . Permission::INTERNAL_FIX_DATA
        ]
    ], function () use ($router) {
        $router->group([
            'prefix' => 'orders/',
            'namespace' => 'OrderIntegration\Controllers',
        ], function () use ($router) {
            $router->post('fix-stocks', 'OrderManualFixController@fixStock');
            $router->post('sync-history-stock-logs', 'OrderManualFixController@syncHistoryStockLog');
            $router->post('storage-fee-arrears', 'OrderManualFixController@storageFeeArrear');
            $router->post('auto-inspection', 'OrderManualFixController@autoInspection');
            $router->post('remove-stock', 'OrderManualFixController@removeStock');
            $router->post('update-order-packing', 'OrderManualFixController@updateOrderPacking');
            $router->post('/change-status', 'OrderManualFixController@changeStatus');
            $router->post('/create-shopee-document', 'OrderManualFixController@createShopeeDocument');
            $router->post('/make-freight-bill', 'OrderManualFixController@makeFreightBillCode');
            $router->post('/update-freight-bills', 'OrderManualFixController@importFreightBill');
            $router->post('/update-payment-data', 'OrderManualFixController@updatePaymentData');
        });
        $router->group([
            'prefix' => 'shipping-partners/',
            'namespace' => 'OrderIntegration\Controllers',
        ], function () use ($router) {
            $router->post('/{shippingPartnerCode}/import-expected-transporting-prices', 'ShippingPartnerManualFixController@importExpectedTransportingPrice');
        });
        $router->group([
            'prefix' => 'skus/',
            'namespace' => 'OrderIntegration\Controllers',
        ], function () use ($router) {
            $router->put('/{skuCode}/update', 'SkuManualFixController@update');
        });
        $router->group([
            'prefix' => 'purchasing-packages/',
            'namespace' => 'OrderIntegration\Controllers',
        ], function () use ($router) {
            $router->put('/{packageCode}/cancel', 'PurchasingPackageManualFixController@cancel');
        });
    });

    $router->group([
        'prefix' => 'orders/',
        'namespace' => 'OrderIntegration\Controllers',
    ], function () use ($router) {
        $router->post('/', 'OrderIntegrationController@create');

        $router->group([
            'prefix' => '{code}',
        ], function () use ($router) {
            $router->put('/shipping-partner', 'OrderIntegrationController@shippingPartner');
            $router->put('/sku', 'OrderIntegrationController@updateOrderSKU');
            $router->put('/', 'OrderIntegrationController@update');
            $router->get('/', 'OrderIntegrationController@detail');
        });
    });

    $router->group([
        'prefix' => 'purchasing_packages/',
        'namespace' => 'PackageIntegration\Controllers',
    ], function () use ($router) {
        $router->group([
            'prefix' => '{code}',
        ], function () use ($router) {
            $router->get('/', 'purchasingPackageController@detail');
        });
    });

    $router->group([
        'prefix' => 'transactions/',
        'namespace' => 'Transaction\Controllers',
    ], function () use ($router) {
        $router->get('/', 'TransactionIntegrationController@index');
        $router->get('/accounts', 'TransactionIntegrationController@getAccounts');
        $router->post('/{account}/deposit', 'TransactionIntegrationController@deposit');
        $router->post('/{account}/collect', 'TransactionIntegrationController@collect');
    });
});

/**
 * External route
 */
$router->group([
    'prefix' => 'external',
    'middleware' => [
        'auth',
        'can:' . Permission::EXTERNAL_API_CALL
    ]

], function () use ($router) {
    $router->group([
        'prefix' => 'merchants/',
        'namespace' => 'Merchant\Controllers',
    ], function () use ($router) {
        $router->post('/', 'MerchantExternalController@create');
        $router->post('/create-seller', 'MerchantExternalController@createSeller');
        $router->post('/update-seller/{id}', 'MerchantExternalController@updateSeller');
        $router->get('/seller-detail/{id}', 'MerchantExternalController@sellerDetail');
        $router->get('/list-seller', 'MerchantExternalController@listSellers');
        $router->group([
            'prefix' => '{merchantCode}/products',
        ], function () use ($router) {
            $router->get('/', 'MerchantExternalController@listingProduct');
            $router->post('/', 'MerchantExternalController@createProduct');
            $router->get('/{productCode}', 'MerchantExternalController@detailProduct');
            $router->get('/{productCode}/stocks', 'MerchantExternalController@stocksOfProduct');
        });
        $router->group([
            'prefix' => '{merchantCode}/orders',
        ], function () use ($router) {
            $router->get('/', 'MerchantExternalController@listingOrder');
            $router->post('/', 'MerchantExternalController@createOrder');
            $router->get('/{orderCode}', 'MerchantExternalController@detailOrder');
            $router->get('/id/{orderId}', 'MerchantExternalController@detailOrderNew');
        });
    });
    $router->group([
        'prefix' => 'warehouses',
        'namespace' => 'Warehouse\Controllers'
    ], function () use ($router) {
        $router->get('/', 'WarehouseExternalController@index');
    });
    $router->group([
        'prefix' => 'stock',
        'namespace' => 'Stock\Controllers'
    ], function () use ($router) {
        $router->get('/', 'StockExternalController@index');
    });
});

/**
 * External routes
 */
$router->group([
    'prefix' => 'external',
    'middleware' => [
        'auth',
    ]
], function () use ($router) {
    $router->group([
        'prefix' => 'merchants/',
        'namespace' => 'Merchant\Controllers',
    ], function () use ($router) {
        $router->get('{code}', 'MerchantExternalController@detail');
    });
});

/**
 * ShopBase routes
 */
$router->group([
    'prefix' => 'shopbase',
    'namespace' => 'ShopBase\Controllers',
], function () use ($router) {
    $router->group([
        'prefix' => 'order/',
    ], function () use ($router) {
        $router->post('/create/{merchantId}', 'ShopBaseController@createOrder');
    });
});

/*
 * Webhook router
 */
$router->group([
    'prefix' => 'webhook',
], function () use ($router) {
    $router->post('shopee', 'Shopee\Controllers\ShopeeController@webhook');
    $router->post('kiotviet', 'KiotViet\Controllers\KiotVietController@webhook');
    $router->post('lazada', 'Lazada\Controllers\LazadaController@webhook');
    $router->post('tiki', 'Tiki\Controllers\TikiController@webhook');
    $router->post('tiktokshop', 'TikTokShop\Controllers\TikTokShopController@webhook');
    $router->post('shopbase', 'ShopBaseUs\Controllers\ShopBaseUsController@webhook');
    $router->post('sapo', 'Sapo\Controllers\SapoController@webhook');
    $router->post('topship', 'Topship\Controllers\TopshipController@webhook');
});


$router->post('merchant/register', 'Merchant\Controllers\MerchantController@register');

// region Merchant
// endregion
/**
 * Merchant routes
 */
$router->group([
    'prefix' => 'merchant',
    'middleware' => [
        AuthenticateMerchant::class,
        'auth'
    ],
], function () use ($router) {
    $router->get('/balance', 'Merchant\Controllers\MerchantController@balance');
    $router->get('/transactions', 'Merchant\Controllers\MerchantController@transactions');
    $router->get('/download-transactions', 'Merchant\Controllers\MerchantController@downloadTransactions');
    $router->get('/country', 'Merchant\Controllers\MerchantController@getCountry');
    $router->get('/service-pack', 'Merchant\Controllers\MerchantController@servicePack');

    $router->group([
        'prefix' => 'autocomplete',
        'namespace' => 'Merchant\Controllers',
    ], function () use ($router) {
        $router->get('/skus-all', 'AutoCompleteController@skuAll');
    });


    $router->group([
        'prefix' => 'orders',
        'namespace' => 'Order\Controllers',
    ], function () use ($router) {
        $router->get('/', 'MerchantOrderController@index');
        $router->post('/checking-before-create', 'MerchantOrderController@checkingBeforeCreate');
        $router->post('/', 'MerchantOrderController@create');
        $router->get('/finance', 'MerchantOrderController@finance');
        $router->get('/stats', 'MerchantOrderController@stats');
        $router->post('/import', 'MerchantOrderController@import');
        $router->post('/import-bash-order', 'MerchantOrderController@importBashOrder');
        $router->post('/import-freight-bill', 'MerchantOrderController@importFreightBill');
        $router->get('/export', 'MerchantOrderController@export');
        $router->get('/{order}', 'MerchantOrderController@detail');
        $router->post('/{order}', 'MerchantOrderController@update');
        $router->put('/{order}/cancel', 'MerchantOrderController@cancel');
        $router->get('/{order}/logs', 'MerchantOrderController@getLogs');
    });

    $router->group([
        'prefix' => 'sku-combos',
        'namespace' => 'Product\Controllers',
    ], function () use ($router) {
        $router->get('/', 'MerchantSkuComboController@index');
        $router->post('/', 'MerchantSkuComboController@create');
        $router->get('/{id}', 'MerchantSkuComboController@detail');
        $router->post('/{id}/update', 'MerchantSkuComboController@update');
        $router->post('/{id}/images', 'MerchantSkuComboController@uploadImages');
    }

    );

    $router->group([
        'prefix' => 'dropship-orders',
        'namespace' => 'Order\Controllers',
    ], function () use ($router) {
        $router->post('/import', 'MerchantDropshipOrderController@import');
    });

    $router->group([
        'prefix' => 'purchasing-orders',
        'namespace' => 'PurchasingOrder\Controllers',
    ], function () use ($router) {
        $router->get('/', 'MerchantPurchasingOrderController@index');
        $router->get('/{id}', 'MerchantPurchasingOrderController@detail');
        $router->put('/{id}', 'MerchantPurchasingOrderController@update');
        $router->post('/{id}/items/{itemId}/mapping', 'MerchantPurchasingOrderController@purchasingVariantMapping');
    });
    $router->group([
        'prefix' => 'purchasing-managers',
        'namespace' => 'PurchasingManager\Controllers',
    ], function () use ($router) {
        $router->post('/purchasing-accounts', 'MerchantPurchasingAccountController@create');
        $router->get('/purchasing-accounts', 'MerchantPurchasingAccountController@index');
        $router->delete('/purchasing-accounts/{id}', 'MerchantPurchasingAccountController@delete');
    });

    $router->group([
        'prefix' => 'products',
        'namespace' => 'Product\Controllers',
    ], function () use ($router) {
        $router->get('/', 'MerchantProductController@index');
        $router->post('/', 'MerchantProductController@create');
        $router->get('/export', 'MerchantProductController@export');
        $router->get('/download-stock-io', 'MerchantProductController@downloadStockIO');
        $router->get('/categories', 'ProductController@getCategories');
        $router->post('/import-excel', 'MerchantProductController@importExcel');
        $router->get('/{productId}/prices', 'MerchantProductPriceController@prices');
        $router->group([
            'prefix' => '/{product}',
            'middleware' => ['product_of_seller']
        ], function () use ($router) {
            $router->get('/', 'MerchantProductController@detail');
            $router->post('/', 'MerchantProductController@update');
            $router->put('/stop-sell', 'MerchantProductController@stopSell');
        });
    });

    $router->group([
        'prefix' => 'skus',
        'namespace' => 'Product\Controllers',
    ], function () use ($router) {
        $router->get('/{sku}/storage-fee-daily', 'MerchantSkuController@storageFeeDaily');
        $router->put('/{sku}/safety-stock', 'MerchantSkuController@updateSafetyStock');
    });

    $router->group([
        'prefix' => 'locations',
        'namespace' => 'Location\Controllers',
    ], function () use ($router) {
        $router->get('/active', 'LocationController@active');
        $router->get('/', 'LocationController@index');
    });

    $router->group([
        'prefix' => 'purchasing-managers',
        'namespace' => 'PurchasingManager\Controllers',
    ], function () use ($router) {
        $router->get('purchasing-services/suggest', 'PurchasingServiceController@suggest');
        $router->get('purchasing-accounts/suggest', 'PurchasingAccountController@suggest');
    });

    // region services
    // endregion
    $router->group([
        'prefix' => 'services',
        'namespace' => 'Service\Controllers',
    ], function () use ($router) {
        $router->get('/', 'ServiceController@index');
    });

    $router->group([
        'prefix' => 'shipping-partners',
        'namespace' => 'ShippingPartner\Controllers',
    ], function () use ($router) {
        $router->get('/', 'ShippingPartnerController@index');
    });

    $router->group([
        'prefix' => 'warehouses',
        'namespace' => 'Warehouse\Controllers',
    ], function () use ($router) {
        $router->get('/items', 'WarehouseController@items');
        $router->get('/suggest', 'WarehouseController@suggest');
        $router->get('/', 'MerchantWarehouseController@index');
    });

    $router->group([
        'prefix' => 'product-prices',
        'namespace' => 'Product\Controllers',
    ], function () use ($router) {
        $router->get('/{id}', 'MerchantProductPriceController@detail');
        $router->put('/{id}/active', 'MerchantProductPriceController@active');
    });

    $router->group([
        'prefix' => 'options',
        'namespace' => 'Product\Controllers',
    ], function () use ($router) {
        $router->post('/check-delete-value', 'ProductController@checkDeleteOptionValue');
    });

    $router->group([
        'prefix' => 'product-drop-ship',
        'namespace' => 'Product\Controllers',
    ], function () use ($router) {
        $router->get('/', 'MerchantProductDropShipController@index');
        $router->post('/', 'MerchantProductDropShipController@create');
        $router->put('/status', 'MerchantProductDropShipController@updateStatus');
        $router->get('/{id}', 'MerchantProductDropShipController@detail');
        $router->post('/{id}', 'MerchantProductDropShipController@update');
    });

    $router->group([
        'prefix' => 'stock-logs',
        'namespace' => 'Stock\Controllers',
    ], function () use ($router) {
        $router->get('/', 'MerchantStockLogController@index');
        $router->get('/export', 'MerchantStockLogController@export');
    });

    $router->group([
        'prefix' => 'purchasing-packages',
        'namespace' => 'PurchasingPackage\Controllers',
    ], function () use ($router) {
        $router->get('/', 'MerchantPurchasingPackageController@index');
        $router->post('/', 'MerchantPurchasingPackageController@create');
        $router->get('/export', 'MerchantPurchasingPackageController@export');
        $router->get('/{id}', 'MerchantPurchasingPackageController@detail');
        $router->post('/to-transporting', 'MerchantPurchasingPackageController@toTransporting');
        $router->put('/{id}/cancel', 'MerchantPurchasingPackageController@cancel');
    });

    $router->group([
        'prefix' => 'onboardings',
        'namespace' => 'Onboarding\Controllers',
    ], function () use ($router) {
        $router->get('/', 'MerchantOnboardingController@index');
        $router->get('/stats', 'MerchantOnboardingController@stats');
        $router->get('/balance', 'MerchantOnboardingController@balance');
    });

    $router->group([
        'prefix' => 'marketplaces',
        'namespace' => 'Marketplace\Controllers',
    ], function () use ($router) {
        $router->get('{code}/oauth-url', 'MerchantMarketplaceController@oauthUrl');
    });

    $router->group([
        'prefix' => 'stores',
        'namespace' => 'Store\Controllers',
    ], function () use ($router) {
        $router->get('/', 'MerchantStoreController@index');
        $router->post('/', 'MerchantStoreController@create');
        $router->delete('{store}', 'MerchantStoreController@delete');
        $router->put('{store}/warehouse', 'MerchantStoreController@updateWarehouse');
        $router->post('{store}/sync-products', 'MerchantStoreController@syncProducts');
        $router->post('{store}/sync-stock-skus', 'MerchantStoreController@syncStockSkus');
        $router->put('{store}/settings', 'MerchantStoreController@settings');
    });

});


// region Admin
// endregion
/**
 * Admin routes
 */
$router->group([
    'middleware' => [
        'auth',
//        SyncAuthenticatedUser::class,
    ]
], function () use ($router) {
    $router->group(['prefix' => 'auth'], function () use ($router) {
        $router->get('user', 'Auth\Controllers\AuthController@user');
    });

    $router->group([
        'prefix' => 'product-prices',
        'namespace' => 'Product\Controllers',
    ], function () use ($router) {
        $router->get('/', 'ProductPriceController@index');
        $router->get('/{id}', 'ProductPriceController@detail');
        $router->put('/{id}/cancel', ['middleware' => ['can:' . Permission::QUOTATION_CANCEL], 'uses' => 'ProductPriceController@cancel']);
    });

    // region products
    // endregion
    $router->group([
        'prefix' => 'products',
        'namespace' => 'Product\Controllers',
    ], function () use ($router) {
        $router->get('/categories', 'ProductController@getCategories');
        $router->get('/units', 'ProductController@getUnits');
        $router->post('import', ['middleware' => ['can:' . Permission::PRODUCT_CREATE], 'uses' => 'ProductController@import']);
        $router->get('/', ['middleware' => ['can_any:' . Permission::PRODUCT_VIEW_LIST . '|' . Permission::PRODUCT_MANAGE_ALL], 'uses' => 'ProductController@index']);
        $router->get('/download-ref-skus', ['middleware' => ['can_any:' . Permission::PRODUCT_VIEW_LIST . '|' . Permission::PRODUCT_MANAGE_ALL], 'uses' => 'ProductController@downloadRefSkus']);
        $router->post('/import-ref-skus', ['middleware' => ['can_any:' . Permission::PRODUCT_VIEW_LIST . '|' . Permission::PRODUCT_MANAGE_ALL], 'uses' => 'ProductController@importRefSkus']);
        $router->put('/{product}/confirm-weight-volume-for-skus', ['middleware' => ['can_any:' . Permission::PRODUCT_UPDATE . '|' . Permission::PRODUCT_MANAGE_ALL], 'uses' => 'ProductController@confirmWeightVolume']);

        $router->group([
            'prefix' => '{product}',
            'middleware' => ['can:' . Permission::QUOTATION_CREATE . ',product'],
        ], function () use ($router) {
            $router->post('/prices', 'ProductPriceController@create');
        });

        $router->group([
            'prefix' => '{product}',
            'middleware' => ['can:' . Permission::PRODUCT_MANAGE . ',product'],
        ], function () use ($router) {
            $router->get('/', 'ProductController@detail');
            $router->post('/', 'ProductController@update');
            $router->get('/sku-prices', 'ProductController@getSkuPrices');
            $router->get('logs', 'ProductController@getLogs');
            $router->get('/prices', 'ProductPriceController@prices');

            $router->group([
                'prefix' => 'merchants',
            ], function () use ($router) {
                $router->get('/', 'ProductController@getMerchants');
                $router->post('/', 'ProductController@updateMerchant');
            });
        });
    });

    $router->group([
        'prefix' => 'product-drop-ship',
        'namespace' => 'Product\Controllers',
    ], function () use ($router) {
        $router->get('/', ['middleware' => ['can:' . Permission::PRODUCT_VIEW_LIST], 'uses' => 'ProductDropShipController@index']);
    });

    $router->group([
        'prefix' => 'sku-combo-operate',
        'namespace' => 'Product\Controllers',
    ], function () use ($router) {
        $router->get('/', ['middleware' => ['can:' . Permission::PRODUCT_VIEW_LIST], 'uses' => 'SkuComboController@index']);
        $router->get('/{id}', ['middleware' => ['can:' . Permission::PRODUCT_VIEW_DETAIL], 'uses' => 'SkuComboController@detail']);
        $router->get('/{id}/logs', ['middleware' => ['can:' . Permission::PRODUCT_VIEW_DETAIL], 'uses' => 'SkuComboController@getLogs']);

    }
    );

    // region skus
    // endregion
    $router->group([
        'prefix' => 'skus',
        'namespace' => 'Product\Controllers',
    ], function () use ($router) {
        $router->post('/update-list-sku', ['middleware' => ['can_any:' . Permission::PRODUCT_UPDATE . '|' . Permission::PRODUCT_MANAGE_ALL], 'uses' => 'SKUController@updateListSku']);
        $router->get('/', ['middleware' => ['can_any:' . Permission::PRODUCT_VIEW_LIST . '|' . Permission::PRODUCT_MANAGE_ALL], 'uses' => 'SKUController@index']);
        $router->post('/', ['middleware' => ['can:' . Permission::PRODUCT_CREATE], 'uses' => 'SKUController@create']);
        $router->post('import', ['middleware' => ['can:' . Permission::PRODUCT_CREATE], 'uses' => 'SKUController@import']);
        $router->get('/statuses', 'SKUController@getStatuses');
        $router->post('import-price', 'SKUController@importPrice');
        $router->get('/suggest', 'SKUController@suggest');
        $router->put('/prices', 'SKUController@updatePrices');
        $router->put('/status', ['middleware' => ['can:' . Permission::SKU_UPDATE], 'uses' => 'SKUController@updateStatus']);
        $router->get('/selected-skus', ['middleware' => ['can_any:' . Permission::PRODUCT_VIEW_DETAIL . '|' . Permission::PRODUCT_MANAGE_ALL], 'uses' => 'SKUController@selectedSkus']);
        $router->post('/barcode', 'SKUController@barcodeRender');

        $router->group([
            'prefix' => 'external',
        ], function () use ($router) {
            $router->post('/import-fobiz-code', ['middleware' => ['can:' . Permission::SKU_CONFIG_EXTERNAL_CODE], 'uses' => 'SkuExternalController@importFobizSkuCode']);
            $router->get('/', ['middleware' => ['can:' . Permission::SKU_VIEW_LIST_EXTERNAL_CODE], 'uses' => 'SkuExternalController@index']);
        });

        $router->group([
            'prefix' => '{sku}',
            'middleware' => ['can_any:' . Permission::PRODUCT_VIEW_DETAIL . '|' . Permission::PRODUCT_MANAGE_ALL]
        ], function () use ($router) {
            $router->get('/', 'SKUController@detail');
            $router->put('/', ['middleware' => ['can_any:' . Permission::PRODUCT_UPDATE . '|' . Permission::PRODUCT_MANAGE_ALL], 'uses' => 'SKUController@update']);
            $router->get('/stocks', 'SKUController@getStocks');
            $router->get('/order-packings', 'SKUController@orderPackings');
            $router->post('/is-goods-batch', ['middleware' => ['can_any:' . Permission::PRODUCT_UPDATE . '|' . Permission::PRODUCT_MANAGE_ALL], 'uses' => 'BatchOfGoodsController@isGoodsBatch']);
            $router->post('/batch-of-goods', ['middleware' => ['can_any:' . Permission::PRODUCT_UPDATE . '|' . Permission::PRODUCT_MANAGE_ALL], 'uses' => 'BatchOfGoodsController@create']);
            $router->get('/batch-of-goods', ['middleware' => ['can_any:' . Permission::PRODUCT_UPDATE . '|' . Permission::PRODUCT_MANAGE_ALL], 'uses' => 'BatchOfGoodsController@index']);
        });
    });

    $router->group([
        'prefix' => 'invalid-orders',
        'namespace' => 'InvalidOrder\Controllers',
        'middleware' => ['can:' . Permission::ORDER_VIEW_FAILED_ORDER],
    ], function () use ($router) {
        $router->get('/', 'InvalidOrderController@index');
        $router->post('/resync-multi', 'InvalidOrderController@resyncMulti');

        $router->group([
            'prefix' => '{invalidOrder}',
        ], function () use ($router) {
            $router->post('/resync', 'InvalidOrderController@resync');
            $router->delete('/', ['middleware' => ['can:' . Permission::ORDER_REMOVE_FAILED_ORDER], 'uses' => 'InvalidOrderController@delete']);
        });
    });

    // region stocks
    // endregion
    $router->group([
        'prefix' => 'stocks',
        'namespace' => 'Stock\Controllers',
        'middleware' => ['can:' . Permission::STOCK_VIEW],
    ], function () use ($router) {
        $router->post('import', 'StockController@import');
        $router->post('export', 'StockController@export');
        $router->get('/', 'StockController@index');
        $router->get('/group-by-batch', 'StockController@groupByBatch');
        $router->get('/export-storage-fees', 'StockController@exportStorageFee');
        $router->get('/{stock}/storage-fee-daily', 'StockController@storageFeeDaily');
        $router->post('/change-position-stocks', ['middleware' => ['can:' . Permission::OPERATION_ARRANGE], 'uses' => 'StockController@changeWarehouseArea']);
    });

    $router->group([
        'prefix' => 'stock-logs',
        'namespace' => 'Stock\Controllers',
    ], function () use ($router) {
        $router->get('/', 'StockLogController@index');
    });

    $router->group([
        'prefix' => 'users',
        'namespace' => 'User\Controllers',
    ], function () use ($router) {
        $router->get('/merchants', ['middleware' => ['can:' . Permission::USER_MERCHANT_VIEW], 'uses' => 'UserController@listUserMerchants']);
        $router->get('/', 'UserController@index');

        $router->group([
            'prefix' => '{user}',
        ], function () use ($router) {
            $router->get('/', 'UserController@detail');
            $router->post('/merchants', ['middleware' => ['can:' . Permission::USER_MERCHANT_ADD], 'uses' => 'UserController@addMerchant']);
            $router->post('/suppliers', ['middleware' => ['can:' . Permission::ADMIN_ASSIGN_SUPPLIER], 'uses' => 'UserController@addSupplier']);
            $router->post('/warehouses', ['middleware' => ['can:' . Permission::USER_MERCHANT_ADD], 'uses' => 'UserController@addWarehouse']);
            $router->post('/countries', ['middleware' => ['can:' . Permission::USER_MERCHANT_ADD], 'uses' => 'UserController@addCountry']);
        });

    });

    // region merchants
    // endregion
    $router->group([
        'prefix' => 'merchants',
        'namespace' => 'Merchant\Controllers',
    ], function () use ($router) {
        $router->get('/', 'AdminMerchantController@index');
        $router->get('/suggest', 'AdminMerchantController@suggest');
        $router->get('/items', 'AdminMerchantController@items');
        $router->post('/', ['middleware' => ['can:' . Permission::MERCHANT_CREATE], 'uses' => 'AdminMerchantController@create']);

        $router->group([
            'prefix' => '{merchant}',
        ], function () use ($router) {
            $router->get('/sales', 'AdminMerchantController@getSales');
            $router->get('/transactions', ['middleware' => ['can:' . Permission::FINANCE_VIEW_SELLER_WALLET], 'uses' => 'AdminMerchantController@getTransactions']);
            $router->post('/transaction', ['middleware' => ['can:' . Permission::FINANCE_EDIT_SELLER_WALLET], 'uses' => 'AdminMerchantController@createTransaction']);

            $router->group([
                'middleware' => ['can:' . Permission::MERCHANT_CONNECT_SHOP_BASE],
            ], function () use ($router) {
                $router->put('/connect-shop-base', 'AdminMerchantController@connectShopBase');
                $router->put('/disconnect-shop-base', 'AdminMerchantController@disconnectShopBase');
            });

            $router->group([
                'middleware' => ['can:' . Permission::MERCHANT_UPDATE],
            ], function () use ($router) {
                $router->put('/status', 'AdminMerchantController@changeState');
                $router->put('/', 'AdminMerchantController@update');
            });

        });
    });

    // region orders
    // endregion
    $router->group([
        'prefix' => 'orders',
        'namespace' => 'Order\Controllers',
    ], function () use ($router) {
        $router->post('remove-stock', 'OrderController@removeStock');
        $router->post('import', 'OrderController@import');
        $router->post('/', ['middleware' => ['can:' . Permission::ORDER_CREATE], 'uses' => 'OrderController@create']);
        $router->get('/', ['middleware' => ['can:' . Permission::ORDER_VIEW_LIST], 'uses' => 'OrderController@index']);
        $router->get('/export', 'OrderController@export');
        $router->get('/finance', ['middleware' => ['can:' . Permission::FINANCE_VIEW_SELLER_REPORT], 'uses' => 'OrderController@finance']);
        $router->get('/stats', ['middleware' => ['can:' . Permission::FINANCE_VIEW_SELLER_REPORT], 'uses' => 'OrderController@stats']);

        $router->post('/import-for-update', ['middleware' => ['can:' . Permission::ORDER_UPDATE], 'uses' => 'OrderController@importForUpdate']);
        $router->post('/import-for-confirm', ['middleware' => ['can:' . Permission::ORDER_UPDATE], 'uses' => 'OrderController@importForConfirm']);
        $router->post('/import-status', ['middleware' => ['can:' . Permission::ORDER_IMPORT_STATUS], 'uses' => 'OrderController@importStatus']);
        $router->post('/import-freight-bill', ['middleware' => ['can:' . Permission::ORDER_IMPORT_FREIGHT_BILL], 'uses' => 'OrderController@importFreightBill']);
        $router->post('/import-freight-bill-status', ['middleware' => ['can:' . Permission::ORDER_UPDATE], 'uses' => 'OrderController@importFreightBillStatus']);
        $router->post('/import-freight-bill-status-new', ['middleware' => ['can:' . Permission::ORDER_UPDATE], 'uses' => 'OrderController@importFreightBillStatusNew']);


        $router->get('/export-services', 'OrderController@exportServices');
        $router->post('/import-finance-status', [
            'middleware' => ['can:' . Permission::ORDER_CHANGE_FINANCIAL_STATUS],
            'uses' => 'OrderController@importFinanceStatus',
        ]);

        $router->group([
            'prefix' => '{order}',
            'middleware' => ['can:' . Permission::ORDER_VIEW_DETAIL],
        ], function () use ($router) {

            $router->post('/payment-confirm', ['middleware' => ['can:' . Permission::ORDER_UPDATE], 'uses' => 'OrderController@paymentConfirm']);
            $router->post('/inspection', ['middleware' => ['can:' . Permission::ORDER_CREATE], 'uses' => 'OrderController@inspection']);
            $router->put('/delivery', 'OrderController@delivery');
            $router->get('skus/waiting-pick', 'OrderController@getputWaitingPickSkus');
            $router->get('warehouses', 'OrderController@getWarehousesInOrder');
            $router->get('/', 'OrderController@detail');
            $router->put('/', ['middleware' => ['can:' . Permission::ORDER_UPDATE], 'uses' => 'OrderController@update']);
            $router->put('/cancel', ['middleware' => ['can:' . Permission::ORDER_CREATE], 'uses' => 'OrderController@cancel']);
            $router->get('logs', 'OrderController@getLogs');
            $router->put('/shipping-partner', 'OrderController@shippingPartner');
            $router->post('sync', 'OrderController@sync');
        });
    });

    // region order-packings
    // endregion
    $router->group([
        'prefix' => 'order-packings',
        'namespace' => 'OrderPacking\Controllers',
    ], function () use ($router) {
        $router->get('/', ['middleware' => ['can:' . Permission::OPERATION_PREPARATION], 'uses' => 'OrderPackingController@index']);
        $router->post('/tracking-no', ['middleware' => ['can:' . Permission::OPERATION_PREPARATION], 'uses' => 'OrderPackingController@trackingNo']);
        $router->post('/cancel-tracking-no', ['middleware' => ['can:' . Permission::ORDER_CANCEL_FREIGHT_BILL], 'uses' => 'OrderPackingController@cancelTrackingNo']);

        $router->post('/add-warehouse-area', ['uses' => 'OrderPackingController@addWarehouseArea']);
        $router->post('/remove-warehouse-area', ['uses' => 'OrderPackingController@removeWarehouseArea']);
        $router->post('/before-remove-warehouse-area', ['uses' => 'OrderPackingController@beforeRemoveWarehouseArea']);
        $router->post('/add-priority', ['uses' => 'OrderPackingController@addPriority']);

        $router->get('/before-tracking-no', ['middleware' => ['can:' . Permission::OPERATION_PREPARATION], 'uses' => 'OrderPackingController@beforeTrackingNo']);
        $router->get('/grant-picker', ['middleware' => ['can:' . Permission::OPERATION_PREPARATION], 'uses' => 'OrderPackingController@grantPicker']);
        $router->get('/download-list-items', ['middleware' => ['can:' . Permission::OPERATION_PREPARATION], 'uses' => 'OrderPackingController@downloadListItems']);
        $router->get('/download-temp-trackings', ['middleware' => ['can:' . Permission::OPERATION_PREPARATION], 'uses' => 'OrderPackingController@downloadTempTrackings']);
        $router->get('/packing-types', ['uses' => 'OrderPackingController@packingTypes']);
        $router->put('/services', ['uses' => 'OrderPackingController@services']);

        $router->get('/scan', [
                'middleware' => ['can:' . Permission::OPERATION_PREPARATION],
                'uses' => 'OrderPackingController@scan'
            ]
        );
        $router->post('/import-barcode', [
                'middleware' => ['can:' . Permission::OPERATION_PREPARATION],
                'uses' => 'OrderPackingController@importBarcode'
            ]
        );

        $router->get('/scan-list', [
                'middleware' => ['can:' . Permission::OPERATION_PREPARATION],
                'uses' => 'OrderPackingController@scanList'
            ]
        );
        $router->put('/shipping-partner', [
                'middleware' => ['can:' . Permission::OPERATION_PREPARATION],
                'uses' => 'OrderPackingController@shippingPartner'
            ]
        );
    });

    // region picking-sessions
    // endregion
    $router->group([
        'prefix' => 'picking-sessions',
        'namespace' => 'OrderPacking\Controllers',
    ], function () use ($router) {
        $router->get('/processing-picking-session', ['middleware' => ['can:' . Permission::OPERATION_PREPARATION], 'uses' => 'PickingSessionController@processingPickingSession']);
        $router->post('/', ['middleware' => ['can:' . Permission::OPERATION_PREPARATION], 'uses' => 'PickingSessionController@create']);
        $router->get('/{pickingSession}', ['middleware' => ['can:' . Permission::OPERATION_PREPARATION], 'uses' => 'PickingSessionController@detail']);
        $router->get('/{pickingSession}/pieces/{pickingSessionPiece}/picked', ['middleware' => ['can:' . Permission::OPERATION_PREPARATION], 'uses' => 'PickingSessionController@pickedPiece']);
        $router->get('/{pickingSession}/picked', ['middleware' => ['can:' . Permission::OPERATION_PREPARATION], 'uses' => 'PickingSessionController@pickedPickingSession']);
    });

    $router->group([
        'prefix' => 'order-exportings',
        'namespace' => 'OrderExporting\Controllers',
    ], function () use ($router) {
        $router->get('/', ['middleware' => ['can:' . Permission::OPERATION_EXPORT], 'uses' => 'OrderExportingController@index']);
        $router->get('/scan', ['middleware' => ['can:' . Permission::OPERATION_EXPORT], 'uses' => 'OrderExportingController@scan']);
    });

    $router->group([
        'prefix' => 'warehouses',
        'namespace' => 'Warehouse\Controllers',
    ], function () use ($router) {
        $router->get('/items', 'WarehouseController@items');
        $router->get('/suggest', 'WarehouseController@suggest');
        $router->get('/', 'WarehouseController@index');

        $router->post('/', ['middleware' => ['can:' . Permission::WAREHOUSE_CREATE], 'uses' => 'WarehouseController@create']);

        $router->group([
            'prefix' => '{warehouse}',
        ], function () use ($router) {
            $router->get('/stocks', 'WarehouseController@stocks');
            $router->get('/areas', 'WarehouseController@areas');
            $router->post('/areas', ['middleware' => ['can:' . Permission::WAREHOUSE_CREATE_AREA], 'uses' => 'WarehouseController@createArea']);
            $router->get('/', 'WarehouseController@detail');

            $router->group([
                'middleware' => ['can:' . Permission::WAREHOUSE_UPDATE],
            ], function () use ($router) {
                $router->put('/status', 'WarehouseController@changeState');
                $router->put('/', 'WarehouseController@update');
            });
        });
    });

    $router->group([
        'prefix' => 'warehouse-areas',
        'namespace' => 'Warehouse\Controllers',
        'middleware' => ['can:' . Permission::WAREHOUSE_VIEW],
    ], function () use ($router) {
        $router->get('/', 'WarehouseAreaController@index');

        $router->group([
            'prefix' => '{warehouseArea}',
            'middleware' => ['can:' . Permission::WAREHOUSE_CREATE_AREA],
        ], function () use ($router) {
            $router->put('/', 'WarehouseAreaController@updateArea');
            $router->delete('/', 'WarehouseAreaController@deleteArea');
        });
    });


    $router->group([
        'prefix' => 'locations',
        'namespace' => 'Location\Controllers',
    ], function () use ($router) {
        $router->get('/active', 'LocationController@active');
        $router->get('/', 'LocationController@index');
    });

    $router->group([
        'prefix' => 'shipping-partners',
        'namespace' => 'ShippingPartner\Controllers',
    ], function () use ($router) {
        $router->get('/', 'ShippingPartnerController@index');
        $router->get('{shippingPartner}/stamps', 'ShippingPartnerController@stampsUrl');
        $router->get('{shippingPartner}/download-expected-transporting-template',
            'ShippingPartnerController@downloadExpectedTransportingTemplate');
        $router->group([
            'middleware' => ['can:' . Permission::FINANCE_SHIPPING_PARTNER_EXPECTED_TRANSPORTING_PRICES_CONFIG],
        ], function () use ($router) {
            $router->post('{shippingPartner}/upload-expected-transporting-price',
                'ShippingPartnerController@uploadExpectedTransportingPrice');
        });
    });

    $router->group([
        'prefix' => 'packages',
        'namespace' => 'Package\Controllers',
    ], function () use ($router) {

        $router->group([
            'prefix' => '{package}',
        ], function () use ($router) {
            $router->put('/export', 'PackageController@export');
            $router->put('/delivery', 'PackageController@delivery');
            $router->put('/', 'PackageController@update');
            $router->get('/', 'PackageController@detail');
            $router->put('/cancel', 'PackageController@cancel');
        });
    });


    $router->group([
        'prefix' => 'import-histories',
        'namespace' => 'ImportHistory\Controllers',
        'middleware' => ['can:' . Permission::WAREHOUSE_IMPORT_HISTORY],
    ], function () use ($router) {
        $router->get('/', 'ImportHistoryController@index');

        $router->group([
            'prefix' => '{importHistory}',
        ], function () use ($router) {
            $router->get('/', 'ImportHistoryController@detail');
            $router->get('/items', 'ImportHistoryController@items');
        });
    });


    $router->group([
        'prefix' => 'categories',
        'namespace' => 'Category\Controllers',
        'middleware' => ['can:' . Permission::CONFIG_CATEGORIES_VIEW],
    ], function () use ($router) {
        $router->get('/', 'CategoryController@index');
        $router->post('/', ['middleware' => ['can:' . Permission::CONFIG_CATEGORIES_UPDATE], 'uses' => 'CategoryController@create']);

        $router->group([
            'prefix' => '{category}',
        ], function () use ($router) {
            $router->put('/', ['middleware' => ['can:' . Permission::CONFIG_CATEGORIES_UPDATE], 'uses' => 'CategoryController@update']);
            $router->get('/', 'CategoryController@detail');
        });
    });


    $router->group([
        'prefix' => 'suppliers',
        'namespace' => 'Supplier\Controllers',
    ], function () use ($router) {
        $router->get('/', ['middleware' => ['can:' . Permission::OPERATION_VIEW_SUPPLIER], 'uses' => 'SupplierController@index']);

        $router->post('/', ['middleware' => ['can:' . Permission::ADMIN_CREATE_SUPPLIER], 'uses' => 'SupplierController@create']);

        $router->group([
            'prefix' => '{supplier}',
        ], function () use ($router) {
            $router->put('/', ['middleware' => ['can:' . Permission::ADMIN_UPDATE_SUPPLIER], 'uses' => 'SupplierController@update']);
            $router->get('/', ['middleware' => ['can:' . Permission::OPERATION_VIEW_SUPPLIER], 'uses' => 'SupplierController@detail']);
            $router->get('/transactions', ['middleware' => ['can:' . Permission::OPERATION_VIEW_SUPPLIER], 'uses' => 'SupplierController@supplierTransactionHistory']);
            $router->get('/wallets', ['middleware' => ['can:' . Permission::OPERATION_VIEW_SUPPLIER], 'uses' => 'SupplierController@infoWallets']);
        });
    });

    $router->group([
        'prefix' => 'delivery-notes',
        'namespace' => 'DeliveryNote\Controllers',
    ], function () use ($router) {
        $router->post('/', ['middleware' => ['can:' . Permission::DELIVERY_NOTE_CREATE], 'uses' => 'DeliveryNoteController@create']);
        $router->get('/', ['middleware' => ['can:' . Permission::DELIVERY_NOTE_VIEW], 'uses' => 'DeliveryNoteController@index']);

        $router->group([
            'prefix' => '{deliveryNote}',
            'middleware' => ['can:' . Permission::DELIVERY_NOTE_VIEW],
        ], function () use ($router) {
            $router->get('/', 'DeliveryNoteController@detail');
        });
    });

    $router->group([
        'prefix' => 'options',
        'namespace' => 'Product\Controllers',
    ], function () use ($router) {
        $router->post('/check-delete-value', 'ProductController@checkDeleteOptionValue');
    });

    // region document-packings
    // endregion
    /**
     * Chứng từ đóng hàng
     */
    $router->group([
        'prefix' => 'document-packings',
        'namespace' => 'Document\Controllers',
    ], function () use ($router) {
        $router->post('/', [
                'middleware' => ['can:' . Permission::OPERATION_PREPARATION],
                'uses' => 'DocumentPackingController@create'
            ]
        );

        $router->get('/', [
                'middleware' => ['can_any:' . Permission::OPERATION_PREPARATION . '|' . Permission::OPERATION_HISTORY_PREPARATION],
                'uses' => 'DocumentPackingController@index'
            ]
        );
        $router->get('{id}', [
                'middleware' => ['can_any:' . Permission::OPERATION_PREPARATION . '|' . Permission::OPERATION_HISTORY_PREPARATION],
                'uses' => 'DocumentPackingController@detail'
            ]
        );
        $router->get('{id}/order-packings', [
                'middleware' => ['can_any:' . Permission::OPERATION_PREPARATION . '|' . Permission::OPERATION_HISTORY_PREPARATION],
                'uses' => 'DocumentPackingController@orderPackings'
            ]
        );
        $router->get('{id}/sku-stats', [
                'middleware' => ['can_any:' . Permission::OPERATION_PREPARATION . '|' . Permission::OPERATION_HISTORY_PREPARATION],
                'uses' => 'DocumentPackingController@skuStats'
            ]
        );

    });

    /**
     * Chứng từ xuất hàng
     */
    $router->group([
        'prefix' => 'document-exportings',
        'namespace' => 'Document\Controllers',
        'middleware' => ['can_any:' . Permission::OPERATION_EXPORT . '|' . Permission::OPERATION_HISTORY_EXPORT]
    ], function () use ($router) {
        $router->get('/', 'DocumentExportingController@index');
        $router->post('/', ['middleware' => ['can:' . Permission::OPERATION_EXPORT], 'uses' => 'DocumentExportingController@create']);
        $router->post('/checking-warnings', ['middleware' => ['can:' . Permission::OPERATION_EXPORT], 'uses' => 'DocumentExportingController@checkWarning']);
        $router->post('/checking-warning-export', ['middleware' => ['can:' . Permission::OPERATION_EXPORT], 'uses' => 'DocumentExportingController@checkWarningExport']);

        $router->get('/{id}', 'DocumentExportingController@detail');
        $router->put('/{id}', ['middleware' => ['can:' . Permission::OPERATION_EXPORT], 'uses' => 'DocumentExportingController@update']);
        $router->delete('/{id}', ['middleware' => ['can:' . Permission::OPERATION_EXPORT], 'uses' => 'DocumentExportingController@cancel']);
        $router->put('/{id}/export', ['middleware' => ['can:' . Permission::OPERATION_EXPORT], 'uses' => 'DocumentExportingController@export']);
    });

    $router->group([
        'prefix' => 'document-exporting-inventories',
        'namespace' => 'Document\Controllers',
        'middleware' => ['can_any:' . Permission::OPERATION_EXPORT . '|' . Permission::OPERATION_HISTORY_EXPORT]
    ], function () use ($router) {
        $router->post('/', 'DocumentExportingInventoryController@create');
        $router->get('/{id}', 'DocumentExportingInventoryController@detail');
    });

    $router->group([
        'prefix' => 'document-supplier-transactions',
        'namespace' => 'Document\Controllers',
    ], function () use ($router) {
        $router->get('/', 'DocumentSupplierTransactionController@index');
        $router->post('{supplierId}', [
            'middleware' => ['can:' . Permission::FINANCE_CREATE_SUPPLIER_TRANSACTION],
            'uses' => 'DocumentSupplierTransactionController@create'
        ]);
        $router->get('/{id}', 'DocumentSupplierTransactionController@detail');
    });

    $router->group([
        'prefix' => 'document-freight-bill-inventories',
        'namespace' => 'Document\Controllers',
    ], function () use ($router) {
        $router->get('/', ['middleware' => ['can:' . Permission::FINANCE_VIEW_STATEMENT], 'uses' => 'DocumentFreightBillInventoryController@index']);
        $router->put('{id}', ['middleware' => ['can:' . Permission::FINANCE_CONFIRM_STATEMENT], 'uses' => 'DocumentFreightBillInventoryController@update']);
        $router->put('{id}/confirm', ['middleware' => ['can:' . Permission::FINANCE_CONFIRM_STATEMENT], 'uses' => 'DocumentFreightBillInventoryController@confirm']);

        $router->group([
            'middleware' => ['can:' . Permission::FINANCE_CREATE_STATEMENT]
        ], function () use ($router) {
            $router->post('/{id}/update-info', 'DocumentFreightBillInventoryController@updateInfoFreightBill');
            $router->post('/', 'DocumentFreightBillInventoryController@create');
            $router->get('/{id}', 'DocumentFreightBillInventoryController@detail');
            $router->put('/{id}/cancel', 'DocumentFreightBillInventoryController@cancel');
            $router->post('/{id}/export-freight-bill', 'DocumentFreightBillInventoryController@exportFreightBill');
        });
    });

    $router->group([
        'prefix' => 'document-importings',
        'namespace' => 'Document\Controllers',
        'middleware' => ['can_any:' . Permission::OPERATION_IMPORT . '|' . Permission::OPERATION_HISTORY_IMPORT]
    ], function () use ($router) {
        $router->get('/scan', ['middleware' => ['can:' . Permission::OPERATION_IMPORT], 'uses' => 'DocumentImportingController@scan']);
        $router->post('/', ['middleware' => ['can:' . Permission::OPERATION_IMPORT], 'uses' => 'DocumentImportingController@create']);
        $router->get('/scan-purchasing-order', ['middleware' => ['can:' . Permission::OPERATION_IMPORT], 'uses' => 'DocumentImportingController@scanByPurchasingOrder']);
        $router->post('/purchasing-order', ['middleware' => ['can:' . Permission::OPERATION_IMPORT], 'uses' => 'DocumentImportingController@createByPurchasingOrder']);
        $router->get('/', 'DocumentImportingController@index');
        $router->get('/{id}', 'DocumentImportingController@detail');
        $router->get('/{id}/sku-importings', 'DocumentImportingController@skuImportings');
        $router->put('/{id}/sku-importings', [['can:' . Permission::OPERATION_IMPORT], 'uses' => 'DocumentImportingController@updateOrCreateSkuImportings']);
        $router->put('/{id}', ['middleware' => ['can:' . Permission::OPERATION_IMPORT], 'uses' => 'DocumentImportingController@update']);
        $router->put('/{skuImportId}/real-quantity', ['middleware' => ['can:' . Permission::OPERATION_IMPORT], 'uses' => 'DocumentImportingController@updateRealQuantity']);
        $router->put('/{id}/cancel', ['middleware' => ['can:' . Permission::OPERATION_IMPORT], 'uses' => 'DocumentImportingController@cancel']);
        $router->put('/{id}/confirm', ['middleware' => ['can:' . Permission::OPERATION_IMPORT], 'uses' => 'DocumentImportingController@confirm']);
    });

    /**
     * Chứng từ nhập hoàn
     */
    $router->group([
        'prefix' => 'document-importing-return-goods',
        'namespace' => 'Document\Controllers',
        'middleware' => ['can_any:' . Permission::OPERATION_IMPORT . '|' . Permission::OPERATION_HISTORY_IMPORT]
    ], function () use ($router) {
        $router->get('/scan', ['middleware' => ['can:' . Permission::OPERATION_IMPORT], 'uses' => 'DocumentImportingReturnGoodsController@scan']);
        $router->get('/scan-list', ['middleware' => ['can:' . Permission::OPERATION_IMPORT], 'uses' => 'DocumentImportingReturnGoodsController@scanList']);
        $router->put('/services', ['uses' => 'DocumentImportingReturnGoodsController@services']);

        $router->post('/', ['middleware' => ['can:' . Permission::OPERATION_IMPORT], 'uses' => 'DocumentImportingReturnGoodsController@create']);
        $router->get('/{id}', 'DocumentImportingReturnGoodsController@detail');
        $router->put('/{id}', ['middleware' => ['can:' . Permission::OPERATION_IMPORT], 'uses' => 'DocumentImportingReturnGoodsController@update']);
        $router->put('/{id}/update-importing-barcodes', ['middleware' => ['can:' . Permission::OPERATION_IMPORT], 'uses' => 'DocumentImportingReturnGoodsController@updateImportingBarcode']);
        $router->put('/{id}/cancel', ['middleware' => ['can:' . Permission::OPERATION_IMPORT], 'uses' => 'DocumentImportingReturnGoodsController@cancel']);
        $router->put('/{id}/confirm', ['middleware' => ['can:' . Permission::OPERATION_IMPORT], 'uses' => 'DocumentImportingReturnGoodsController@confirm']);
        $router->get('/{id}/export-skus', ['middleware' => ['can:' . Permission::OPERATION_IMPORT], 'uses' => 'DocumentImportingReturnGoodsController@exportSkus']);
    });

    /**
     * Chứng từ kiểm hàng ở kho
     */
    $router->group([
        'prefix' => 'document-sku-inventories',
        'namespace' => 'Document\Controllers'
    ], function () use ($router) {
        $router->get('/', ['middleware' => ['can:' . Permission::OPERATION_HISTORY_AUDIT_VIEW], 'uses' => 'DocumentSkuInventoryController@index']);
        $router->post('/', ['middleware' => ['can:' . Permission::OPERATION_HISTORY_AUDIT_EDIT], 'uses' => 'DocumentSkuInventoryController@create']);
        $router->get('/{id}', ['middleware' => ['can:' . Permission::OPERATION_HISTORY_AUDIT_VIEW], 'uses' => 'DocumentSkuInventoryController@detail']);
        $router->put('/{id}', ['middleware' => ['can:' . Permission::OPERATION_HISTORY_AUDIT_EDIT], 'uses' => 'DocumentSkuInventoryController@update']);
        $router->post('/{id}/scan', ['middleware' => ['can:' . Permission::OPERATION_HISTORY_AUDIT_EDIT], 'uses' => 'DocumentSkuInventoryController@scan']);
        $router->post('/{id}/importing-skus', ['middleware' => ['can:' . Permission::OPERATION_HISTORY_AUDIT_EDIT], 'uses' => 'DocumentSkuInventoryController@importingSku']);
        $router->get('/{id}/scan-histories', ['middleware' => ['can:' . Permission::OPERATION_HISTORY_AUDIT_VIEW], 'uses' => 'DocumentSkuInventoryController@scanHistory']);
        $router->post('/{id}/balanced', ['middleware' => ['can:' . Permission::OPERATION_HISTORY_AUDIT_CONFIRM], 'uses' => 'DocumentSkuInventoryController@balance']);
        $router->post('/{id}/completed', ['middleware' => ['can:' . Permission::OPERATION_HISTORY_AUDIT_CONFIRM], 'uses' => 'DocumentSkuInventoryController@complete']);
    });

    /**
     * Chứng từ đối soát COD
     */
    $router->group([
        'prefix' => 'document-freight-bill-inventories',
        'namespace' => 'Document\Controllers',
    ], function () use ($router) {
        $router->get('/', ['middleware' => ['can:' . Permission::FINANCE_VIEW_STATEMENT], 'uses' => 'DocumentFreightBillInventoryController@index']);
        $router->put('{id}', ['middleware' => ['can:' . Permission::FINANCE_CONFIRM_STATEMENT], 'uses' => 'DocumentFreightBillInventoryController@update']);
        $router->put('{id}/confirm', ['middleware' => ['can:' . Permission::FINANCE_CONFIRM_STATEMENT], 'uses' => 'DocumentFreightBillInventoryController@confirm']);

        $router->group([
            'middleware' => ['can:' . Permission::FINANCE_CREATE_STATEMENT]
        ], function () use ($router) {
            $router->post('/', 'DocumentFreightBillInventoryController@create');
            $router->get('/{id}', 'DocumentFreightBillInventoryController@detail');
            $router->put('/{id}/cancel', 'DocumentFreightBillInventoryController@cancel');
            $router->post('/{id}/export-freight-bill', 'DocumentFreightBillInventoryController@exportFreightBill');
        });
    });

    /**
     * Chứng từ đối soát giao nhận
     */
    $router->group([
        'prefix' => 'document-delivery-comparisons',
        'namespace' => 'Document\Controllers',
    ], function () use ($router) {
        $router->group([
            'middleware' => ['can:' . Permission::FINANCE_CREATE_DELIVERY_STATEMENT]
        ], function () use ($router) {
            $router->post('/', 'DocumentDeliveryComparisonController@create');
            $router->post('/checking', 'DocumentDeliveryComparisonController@checking');
        });
        $router->group([
            'middleware' => ['can:' . Permission::FINANCE_VIEW_DELIVERY_STATEMENT]
        ], function () use ($router) {
            $router->get('/', 'DocumentDeliveryComparisonController@index');
            $router->put('/{documentDeliveryComparison}', 'DocumentDeliveryComparisonController@update');
            $router->get('/{documentDeliveryComparison}', 'DocumentDeliveryComparisonController@detail');
            $router->get('/{documentDeliveryComparison}/download-error-comparison', 'DocumentDeliveryComparisonController@downloadErrorComparison');
        });
    });

    $router->group([
        'prefix' => 'purchasing-managers',
        'namespace' => 'PurchasingManager\Controllers',
    ], function () use ($router) {
        $router->post('/purchasing-accounts', ['middleware' => ['can:' . Permission::MERCHANT_CONNECT_PURCHASING], 'uses' => 'PurchasingAccountController@create']);
        $router->get('/purchasing-accounts', ['middleware' => ['can:' . Permission::MERCHANT_CONNECT_PURCHASING], 'uses' => 'PurchasingAccountController@index']);
        $router->put('/purchasing-accounts/{id}', ['middleware' => ['can:' . Permission::MERCHANT_CONNECT_PURCHASING], 'uses' => 'PurchasingAccountController@update']);
        $router->delete('/purchasing-accounts/{id}', ['middleware' => ['can:' . Permission::MERCHANT_CONNECT_PURCHASING], 'uses' => 'PurchasingAccountController@delete']);
        $router->get('/purchasing-accounts/{id}/balance', ['middleware' => ['can:' . Permission::MERCHANT_CONNECT_PURCHASING], 'uses' => 'PurchasingAccountController@balance']);

        $router->get('purchasing-services/suggest', 'PurchasingServiceController@suggest');
        $router->get('purchasing-accounts/suggest', 'PurchasingAccountController@suggest');
    });

    $router->group([
        'prefix' => 'purchasing-orders',
        'namespace' => 'PurchasingOrder\Controllers',
        'middleware' => ['can_any:' . Permission::MERCHANT_PURCHASING_ORDER_ALL . '|' . Permission::MERCHANT_PURCHASING_ORDER_ASSIGNED]
    ], function () use ($router) {
        $router->post(
            '/{id}/purchasing-variants/{purchasingVariantId}/mapping',
            ['middleware' => ['can_any:' . Permission::MERCHANT_SKU_MAP_ALL . '|' . Permission::MERCHANT_SKU_MAP_ASSIGNED], 'uses' => 'PurchasingOrderController@purchasingVariantMapping']
        );
        $router->get('/', 'PurchasingOrderController@index');
        $router->get('/{id}', 'PurchasingOrderController@detail');
    });

    $router->group([
        'prefix' => 'purchasing-packages',
        'namespace' => 'PurchasingPackage\Controllers',
        'middleware' => ['can_any:' . Permission::FINANCE_VIEW_INBOUND_SHIPMENT]
    ], function () use ($router) {
        $router->get('/', 'PurchasingPackageController@index');
        $router->post('/', [
            'middleware' => ['can:' . Permission::ADMIN_PACKAGE_CREATE],
            'uses' => 'PurchasingPackageController@create',
        ]);

        $router->get('/export', 'PurchasingPackageController@export');
        $router->post('/import-finance-status', [
            'middleware' => ['can:' . Permission::ORDER_CHANGE_FINANCIAL_STATUS],
            'uses' => 'PurchasingPackageController@importFinanceStatus',
        ]);
        $router->group([
            'prefix' => '{purchasingPackage}'
        ], function () use ($router) {
            $router->put('/add-items', 'PurchasingPackageController@addItems');
        });
    });

    $router->group([
        'prefix' => 'marketplaces',
        'namespace' => 'Marketplace\Controllers',
    ], function () use ($router) {
        $router->get('/', 'MarketplaceController@index');
        $router->get('{code}/oauth-url', [
            'middleware' => ['can:' . Permission::MERCHANT_MANAGE_STORE],
            'uses' => 'MarketplaceController@oauthUrl',
        ]);
        $router->post('{code}', [
            'middleware' => ['can:' . Permission::MERCHANT_MANAGE_STORE],
            'uses' => 'MarketplaceController@registerStore',
        ]);
    });

    $router->group([
        'prefix' => 'stores',
        'namespace' => 'Store\Controllers',
        'middleware' => ['can:' . Permission::MERCHANT_MANAGE_STORE],
    ], function () use ($router) {
        $router->get('/', 'StoreController@index');
        $router->delete('{store}', 'StoreController@delete');
        $router->post('import', 'StoreController@import');
    });

    $router->group([
        'prefix' => 'warehouse-stocks',
        'namespace' => 'WarehouseStock\Controllers',
        'middleware' => ['can:' . Permission::STOCK_VIEW],
    ], function () use ($router) {
        $router->get('/', 'WarehouseStockController@index');
    });

    $router->group([
        'prefix' => 'store-skus',
        'namespace' => 'Store\Controllers',
    ], function () use ($router) {
        $router->get('/', ['middleware' => ['can:' . Permission::SKU_VIEW_LIST_EXTERNAL_CODE], 'uses' => 'StoreSkuController@index']);

        $router->group([
            'prefix' => '{storeSku}',
        ], function () use ($router) {
            $router->put('/', ['middleware' => ['can_any:' . Permission::PRODUCT_UPDATE . '|' . Permission::PRODUCT_MANAGE_ALL], 'uses' => 'StoreSkuController@update']);
            $router->delete('/', ['middleware' => ['can_any:' . Permission::PRODUCT_UPDATE . '|' . Permission::PRODUCT_MANAGE_ALL], 'uses' => 'StoreSkuController@delete']);
        });
    });

    // region services
    // endregion
    $router->group([
        'prefix' => 'services',
        'namespace' => 'Service\Controllers',
    ], function () use ($router) {
        $router->post('/', ['middleware' => ['can:' . Permission::SERVICE_ADD], 'uses' => 'ServiceController@create']);
        $router->put('/{service}', ['middleware' => ['can:' . Permission::SERVICE_ADD], 'uses' => 'ServiceController@update']);
        $router->post('/update-all-merchants', ['middleware' => ['can:' . Permission::SERVICE_ADD], 'uses' => 'ServiceController@updateServicePriceAllMerchants']);
        $router->get('/', ['middleware' => ['can:' . Permission::SERVICE_VIEW], 'uses' => 'ServiceController@index']);
        $router->put('/{service}/is-required', ['middleware' => ['can:' . Permission::SERVICE_ADD], 'uses' => 'ServiceController@isRequired']);
        $router->put('/{service}/status', ['middleware' => ['can:' . Permission::SERVICE_STOP], 'uses' => 'ServiceController@changeStatus']);
        $router->post('/{service}/service-prices', ['middleware' => ['can:' . Permission::SERVICE_ADD], 'uses' => 'ServiceController@createServicePrice']);
        $router->get('/{service}/service-prices', ['middleware' => ['can:' . Permission::SERVICE_VIEW], 'uses' => 'ServiceController@listingServicePrice']);
        $router->put('/{service}/service-prices/{servicePrice}', ['middleware' => ['can:' . Permission::SERVICE_ADD], 'uses' => 'ServiceController@updateServicePrice']);
        $router->put('/{service}/service-prices/{servicePrice}/is-default', ['middleware' => [], 'uses' => 'ServiceController@isDefault']);
    });
    $router->group([
        'prefix' => 'active-code',
        'namespace' => 'ActiveCode\Controllers',
    ], function () use ($router) {
        $router->post('/create', ['middleware' => ['can:' . Permission::SERVICE_ADD], 'uses' => 'ActiveCodeController@create']);
        $router->get('/{id}', 'ActiveCodeController@listCode');
    });

    // region service-packs
    // endregion
    $router->group([
        'prefix' => 'service-packs',
        'namespace' => 'Service\Controllers',
    ], function () use ($router) {
        $router->get('/', ['middleware' => ['can:' . Permission::SERVICE_VIEW], 'uses' => 'ServicePackController@index']);
        $router->post('/', ['middleware' => ['can:' . Permission::SERVICE_ADD], 'uses' => 'ServicePackController@create']);
        $router->get('/{servicePack}', ['middleware' => ['can:' . Permission::SERVICE_VIEW], 'uses' => 'ServicePackController@detail']);
        $router->put('/{servicePack}', ['middleware' => ['can:' . Permission::SERVICE_ADD], 'uses' => 'ServicePackController@update']);
        $router->post('/{servicePack}/add-sellers', ['middleware' => ['can:' . Permission::SERVICE_ADD], 'uses' => 'ServicePackController@addSeller']);
        $router->get('/{servicePack}/seller-histories', ['middleware' => ['can:' . Permission::SERVICE_VIEW], 'uses' => 'ServicePackController@sellerHistory']);
    });

    // region service-combos
    // endregion
    $router->group([
        'prefix' => 'service-combos',
        'namespace' => 'Service\Controllers',
    ], function () use ($router) {
        $router->post('/', ['middleware' => ['can:' . Permission::SERVICE_ADD], 'uses' => 'ServiceComboController@create']);
        $router->get('/{serviceCombo}', ['middleware' => ['can:' . Permission::SERVICE_VIEW], 'uses' => 'ServiceComboController@detail']);
    });

    $router->group([
        'prefix' => 'system',
        'middleware' => ['can:' . Permission::SYSTEM_DATA_OPS],
    ], function () use ($router) {
        $router->post('shoppe/sync-orders', 'Shopee\Controllers\ShopeeController@syncOrders');
    });

    $router->group([
        'prefix' => 'tenant-settings',
        'namespace' => 'Tenant\Controllers',
    ], function () use ($router) {
        $router->post('/update-banner', 'TenantSettingController@updateBanner');
        $router->post('/', ['middleware' => ['can:' . Permission::ADMIN_SET_ORDER_FLOW], 'uses' => 'TenantSettingController@settings']);
        $router->post('/document-setting', ['middleware' => ['can:' . Permission::ADMIN_SYSTEM_CONFIG], 'uses' => 'TenantSettingController@settingDocumentImporting']);
        $router->get('/', 'TenantSettingController@getSetting');
    });

    $router->group([
        'prefix' => 'debt-shipping-partners',
        'namespace' => 'Document\Controllers',
    ], function () use ($router) {
        $router->get('/', ['middleware' => ['can:' . Permission::FINANCE_VIEW_STATEMENT], 'uses' => 'DebtShippingPartnerController@index']);
        $router->get('/stats', ['middleware' => ['can:' . Permission::FINANCE_VIEW_STATEMENT], 'uses' => 'DebtShippingPartnerController@stats']);
        $router->post('/export-excel', ['middleware' => ['can:' . Permission::FINANCE_VIEW_STATEMENT], 'uses' => 'DebtShippingPartnerController@exportExcel']);
    });

});
