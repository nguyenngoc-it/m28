<?php

use Modules\Auth\Services\Permission;

$router->group([
    'prefix' => 'api',
    'middleware' => [
        'auth',
        'can:' . Permission::EXTERNAL_API_CALL
    ]

], function () use ($router) {
    $router->group([
        'prefix' => 'orders/',
        'namespace' => 'Order\Controllers\Api\V1',
    ], function () use ($router) {
        $router->get('/', 'OrderApiController@index');
        $router->post('/', 'OrderApiController@create');
        $router->get('/{orderId}', 'OrderApiController@detail');
        $router->put('/{orderId}/cancel', 'OrderApiController@cancel');
        $router->put('/', 'OrderApiController@update');
    });

    $router->group([
        'prefix' => 'products/',
        'namespace' => 'Product\Controllers\Api\V1',
    ], function () use ($router) {
        $router->get('/', 'ProductApiController@index');
        $router->post('/', 'ProductApiController@create');
        $router->get('/{productId}', 'ProductApiController@detail');
        $router->put('/{productId}', 'ProductApiController@update');
        $router->post('/{productId}/images', 'ProductApiController@uploadImges');
    });

    $router->group([
        'prefix' => 'stores/',
        'namespace' => 'Store\Controllers\Api\V1',
    ], function () use ($router) {
        $router->get('/', 'MerchantStoreController@index');
        $router->post('/', 'MerchantStoreController@create');
        $router->delete('{store}', 'MerchantStoreController@delete');
        $router->put('{store}/warehouse', 'MerchantStoreController@updateWarehouse');
        $router->post('{store}/sync-products', 'MerchantStoreController@syncProducts');
    });

    $router->group([
        'prefix' => 'oauth/marketplaces/',
        'namespace' => 'Marketplace\Controllers\Api\V1',
    ], function () use ($router) {
        $router->get('{code}/oauth-callback', 'MerchantMarketplaceController@oauthCallback');
        $router->get('{code}/oauth-url', 'MerchantMarketplaceController@oauthUrl');
    });

    $router->group([
        'prefix' => 'purchasing-packages',
        'namespace' => 'PurchasingPackage\Controllers\Api\V1',
    ], function () use ($router) {
        $router->get('/', 'MerchantPurchasingPackageApiController@index');
        $router->post('/', 'MerchantPurchasingPackageApiController@create');
        $router->get('/export', 'MerchantPurchasingPackageApiController@export');
        $router->get('/{id}', 'MerchantPurchasingPackageApiController@detail');
        $router->put('{id}/cancel', 'MerchantPurchasingPackageApiController@cancel');
    });
    $router->group([
        'prefix' => 'purchasing-manager',
        'namespace' => 'PurchasingManager\Controllers\Api\V1',
    ], function () use ($router) {
        $router->get('/', 'MerchantPurchasingAccountApiController@index');
        $router->post('/', 'MerchantPurchasingAccountApiController@create');
        $router->delete('/{id}', 'MerchantPurchasingAccountApiController@delete');
        $router->get('/purchasing-services', 'MerchantPurchasingAccountApiController@purchasingServices');
    });
    $router->group([
        'prefix' => 'purchasing-order',
        'namespace' => 'PurchasingOrder\Controllers\Api\V1',
    ], function () use ($router) {
        $router->get('/', 'MerchantPurchasingOrderApiController@index');
        $router->get('/{id}', 'MerchantPurchasingOrderApiController@detail');
        $router->get('/{id}/items/{itemId}/mapping', 'MerchantPurchasingOrderApiController@mapping');
        $router->put('/{id}', 'MerchantPurchasingOrderApiController@update');

    });
    $router->group([
        'prefix' => 'sku-combos',
        'namespace' => 'Product\Controllers\Api\V1',
    ], function () use ($router) {
        $router->get('/', 'SkuComboApiController@index');
        $router->post('/', 'SkuComboApiController@create');
        $router->put('/{id}', 'SkuComboApiController@update');
    });

    $router->group([
        'prefix' => 'warehouses',
        'namespace' => 'Warehouse\Controllers\Api\V1',
    ], function () use ($router) {
        $router->get('/', 'WarehouseApiController@index');
    });

    $router->group([
        'prefix' => 'categories',
        'namespace' => 'Category\Controllers\Api\V1',
    ], function () use ($router) {
        $router->get('/', 'CategoryApiController@index');
    });
});

$router->group([
    'prefix' => 'oauth/marketplaces/',
    'namespace' => 'Marketplace\Controllers\Api\V1',
], function () use ($router) {
    $router->get('{code}/oauth-callback', 'MerchantMarketplaceController@oauthCallback');
    $router->get('{code}/oauth-url', 'MerchantMarketplaceController@oauthUrl');
});
