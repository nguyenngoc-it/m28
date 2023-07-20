<?php

namespace App\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;
use Modules\Document\Events\DocumentCodComparisonCreated;
use Modules\Document\Events\DocumentDeliverComparisonCreated;
use Modules\Document\Events\DocumentExportingCreated;
use Modules\Document\Events\DocumentExportingExported;
use Modules\Document\Events\DocumentPackingCreated;
use Modules\Document\Events\DocumentReturnGoodsCreated;
use Modules\Document\Events\DocumentSupplierTransactionCreated;
use Modules\Document\Listeners\DocumentCodComparisonCreatedListener;
use Modules\Document\Listeners\DocumentDeliverComparisonCreatedListener;
use Modules\Document\Listeners\DocumentExportingCreatedListener;
use Modules\Document\Listeners\DocumentExportingExportedListener;
use Modules\Document\Listeners\DocumentPackingCreatedListener;
use Modules\Document\Listeners\DocumentReturnGoodsCreatedListener;
use Modules\Document\Listeners\DocumentSupplierTransactionCreatedListener;
use Modules\EventBridge\Services\EventListener as EventBridgeListener;
use Modules\Merchant\Events\MerchantCreated;
use Modules\Merchant\Listeners\MerchantCreatedListener;
use Modules\Order\Events\OrderAttributesChanged;
use Modules\Order\Events\OrderCreated;
use Modules\Order\Events\OrderExported;
use Modules\Order\Events\OrderInspected;
use Modules\Order\Events\OrderReturned;
use Modules\Order\Events\OrderShippingFinancialStatusChanged;
use Modules\Order\Events\OrderSkusChanged;
use Modules\Order\Events\OrderSkusCompletedBatch;
use Modules\Order\Events\OrderSkusUpdatedBatch;
use Modules\Order\Events\OrderStatusChanged;
use Modules\Order\Events\OrderStockCreated;
use Modules\Order\Events\OrderStockDeleted;
use Modules\Order\Events\OrderUpdatedShippingPartner;
use Modules\Order\Listeners\CreatedOrderListener;
use Modules\Order\Listeners\InspectedOrderListener;
use Modules\Order\Listeners\OrderAttributesChangedListener;
use Modules\Order\Listeners\OrderExportedListener;
use Modules\Order\Listeners\OrderReturnedListener;
use Modules\Order\Listeners\OrderShippingFinancialStatusChangedListener;
use Modules\Order\Listeners\OrderSkusChangedListener;
use Modules\Order\Listeners\OrderSkusCompletedBatchListener;
use Modules\Order\Listeners\OrderSkusUpdatedBatchListener;
use Modules\Order\Listeners\OrderStockCreatedListener;
use Modules\Order\Listeners\OrderStockDeletedListener;
use Modules\Order\Listeners\PublishOrderWebhookEventListener;
use Modules\Order\Listeners\UpdatedShippingPartnerOrderListener;
use Modules\OrderPacking\Events\OrderPackingCreated;
use Modules\OrderPacking\Events\OrderPackingServiceUpdated;
use Modules\OrderPacking\Listeners\CreatedOrderPackingListener;
use Modules\OrderPacking\Listeners\OrderPackingServiceUpdatedListener;
use Modules\Product\Events\BatchOfGoodCreated;
use Modules\Product\Events\ProductCreated;
use Modules\Product\Events\ProductUpdated;
use Modules\Product\Events\SkuComboAttributesUpdated;
use Modules\Product\Events\SkuComboSkuUpdated;
use Modules\Product\Events\SkuIsGoodsBatchUpdated;
use Modules\Product\Listeners\BatchOfGoodCreatedListener;
use Modules\Product\Listeners\SkuComboAttributesUpdatedListener;
use Modules\Product\Listeners\AutoSetServiceProductListener;
use Modules\Product\Listeners\AutoSetServiceProductWhenUpdateListener;
use Modules\Product\Listeners\LogProductActivityListener;
use Modules\Product\Listeners\ProductCreatedListener;
use Modules\Product\Listeners\PublishSkuWebhookEventListener;
use Modules\Product\Listeners\SkuComboSkuUpdatedListener;
use Modules\Product\Listeners\SkuIsGoodsBatchUpdatedListener;
use Modules\Service\Events\ServiceComboCreated;
use Modules\Service\Events\ServicePackCreated;
use Modules\Service\Events\ServicePackPriceAdded;
use Modules\Service\Events\ServicePackPriceRemoved;
use Modules\Service\Events\ServicePackSellerAdded;
use Modules\Service\Events\ServicePackSellerRemoved;
use Modules\Service\Listeners\ServiceComboCreatedListener;
use Modules\Service\Listeners\ServicePackCreatedListener;
use Modules\Service\Listeners\ServicePackPriceAddedListener;
use Modules\Service\Listeners\ServicePackPriceRemovedListener;
use Modules\Service\Listeners\ServicePackSellerAddedListener;
use Modules\Service\Listeners\ServicePackSellerRemovedListener;
use Modules\Stock\Events\StockChanged;
use Modules\Stock\Listeners\SyncStockQuantityListener;
use Modules\SupplierTransaction\Events\SupplierTransactionCompleted;
use Modules\SupplierTransaction\Listeners\SupplierTransactionCompletedListener;
use Modules\User\Events\UserAddedCountry;
use Modules\User\Listeners\UserAddedCountryListener;
use Modules\Warehouse\Events\WarehouseCreated;
use Modules\Warehouse\Listeners\WarehouseCreatedListener;
use Modules\PurchasingPackage\Events\PurchasingPackageStatusChangedEvent;
use Modules\PurchasingPackage\Listeners\PurchasingPackageStatusChangedListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        /**
         * Order
         */
        OrderCreated::class => [
            CreatedOrderListener::class,
            EventBridgeListener::class,
        ],
        OrderStatusChanged::class => [
            PublishOrderWebhookEventListener::class,
            EventBridgeListener::class,
        ],
        OrderShippingFinancialStatusChanged::class => [
            OrderShippingFinancialStatusChangedListener::class,
        ],
        OrderInspected::class => [
            InspectedOrderListener::class
        ],
        OrderStockCreated::class => [
            OrderStockCreatedListener::class
        ],
        OrderStockDeleted::class => [
            OrderStockDeletedListener::class
        ],
        OrderSkusChanged::class => [
            OrderSkusChangedListener::class
        ],
        OrderAttributesChanged::class => [
            OrderAttributesChangedListener::class
        ],
        OrderUpdatedShippingPartner::class => [
            UpdatedShippingPartnerOrderListener::class
        ],
        OrderSkusUpdatedBatch::class => [
            OrderSkusUpdatedBatchListener::class
        ],
        OrderSkusCompletedBatch::class => [
            OrderSkusCompletedBatchListener::class
        ],
        OrderExported::class => [
            OrderExportedListener::class
        ],
        OrderReturned::class => [
            OrderReturnedListener::class
        ],
        /**
         * OrderPacking
         */
        OrderPackingCreated::class => [
            CreatedOrderPackingListener::class
        ],
        OrderPackingServiceUpdated::class => [
            OrderPackingServiceUpdatedListener::class
        ],

        /**
         * Product
         */
        ProductCreated::class => [
            ProductCreatedListener::class,
            AutoSetServiceProductListener::class,
        ],
        ProductUpdated::class => [
            LogProductActivityListener::class,
            AutoSetServiceProductWhenUpdateListener::class,
        ],

        StockChanged::class => [
            SyncStockQuantityListener::class,
            PublishSkuWebhookEventListener::class,
        ],

        SkuIsGoodsBatchUpdated::class => [
            SkuIsGoodsBatchUpdatedListener::class
        ],

        /**
         * Sku Combo
         */

        SkuComboAttributesUpdated::class => [
            SkuComboAttributesUpdatedListener::class,
        ],
        SkuComboSkuUpdated::class => [
            SkuComboSkuUpdatedListener::class,
        ],

        /**
         * Batch of goods
         */
        BatchOfGoodCreated::class => [
            BatchOfGoodCreatedListener::class
        ],

        /**
         * User
         */
        UserAddedCountry::class => [
            UserAddedCountryListener::class,
        ],

        /**
         * Merchant
         */
        MerchantCreated::class => [
            MerchantCreatedListener::class,
        ],

        /**
         * Warehouse
         */
        WarehouseCreated::class => [
            WarehouseCreatedListener::class,
        ],

        /**
         * PurchasingPackage
         */
        PurchasingPackageStatusChangedEvent::class => [
            PurchasingPackageStatusChangedListener::class
        ],

        /**
         * Document
         */
        DocumentReturnGoodsCreated::class => [
            DocumentReturnGoodsCreatedListener::class
        ],
        DocumentPackingCreated::class => [
            DocumentPackingCreatedListener::class
        ],
        DocumentExportingCreated::class => [
            DocumentExportingCreatedListener::class
        ],
        DocumentExportingExported::class => [
            DocumentExportingExportedListener::class
        ],
        DocumentCodComparisonCreated::class => [
            DocumentCodComparisonCreatedListener::class
        ],
        DocumentDeliverComparisonCreated::class => [
            DocumentDeliverComparisonCreatedListener::class
        ],
        DocumentSupplierTransactionCreated::class => [
            DocumentSupplierTransactionCreatedListener::class
        ],

        /**
         * Service
         */
        ServicePackCreated::class => [
            ServicePackCreatedListener::class
        ],
        ServicePackPriceAdded::class => [
            ServicePackPriceAddedListener::class
        ],
        ServicePackPriceRemoved::class => [
            ServicePackPriceRemovedListener::class
        ],
        ServicePackSellerAdded::class => [
            ServicePackSellerAddedListener::class
        ],
        ServicePackSellerRemoved::class => [
            ServicePackSellerRemovedListener::class
        ],
        ServiceComboCreated::class => [
            ServiceComboCreatedListener::class
        ],
        SupplierTransactionCompleted::class => [
            SupplierTransactionCompletedListener::class
        ]
    ];
}
