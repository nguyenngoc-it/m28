<?php

return [
    /*
     * Map model to transformer
     */
    'transformers' => [
        \App\Base\Validator::class => \Modules\App\Transformers\ValidatorTransformer::class,
        \Modules\User\Models\User::class => \Modules\User\Transformers\UserTransformer::class,
        \Modules\OrderPacking\Models\OrderPacking::class => \Modules\OrderPacking\Transformers\OrderPackingTransformer::class,
        \Modules\OrderPacking\Models\OrderPackingItem::class => \Modules\OrderPacking\Transformers\OrderPackingItemTransformer::class,
        \Modules\OrderExporting\Models\OrderExporting::class => \Modules\OrderExporting\Transformers\OrderExportingTransformer::class,
        \Modules\OrderExporting\Models\OrderExportingItem::class => \Modules\OrderExporting\Transformers\OrderExportingItemTransformer::class,
        \Modules\FreightBill\Models\FreightBill::class => \Modules\FreightBill\Transformers\FreightBillTransformer::class,
        \Modules\Document\Models\Document::class => \Modules\Document\Transformers\DocumentTransformer::class,
        \Modules\Document\Models\DocumentOrderInventory::class => \Modules\Document\Transformers\DocumentOrderInventoryTransformer::class,
        \Modules\PurchasingManager\Models\PurchasingAccount::class => \Modules\PurchasingManager\Transformers\PurchasingAccountTransformer::class,
        \Modules\PurchasingOrder\Models\PurchasingOrder::class => \Modules\PurchasingOrder\Transformers\PurchasingOrderTransformer::class,
        \Modules\Marketplace\Services\MarketplaceInterface::class => \Modules\Marketplace\Transformers\MarketplaceTransformer::class,
        \Modules\PurchasingOrder\Models\PurchasingVariant::class => \Modules\PurchasingOrder\Transformers\PurchasingVariantTransformer::class,
        \Modules\Document\Models\DocumentSkuInventory::class => \Modules\Document\Transformers\DocumentSkuInventoryTransformer::class,
        \Modules\Document\Models\ImportingBarcode::class => \Modules\Document\Transformers\ImportingBarcodeTransformer::class,
        \Modules\PurchasingPackage\Models\PurchasingPackage::class => \Modules\PurchasingPackage\Transformers\PurchasingPackageTransformer::class,
        \Modules\Document\Models\DocumentFreightBillInventory::class => \Modules\Document\Transformers\DocumentFreightBillInventoryTransformer::class,
        \Modules\Store\Models\Store::class => \Modules\Store\Transformers\StoreTransformer::class,
        \Modules\Service\Models\ServicePack::class => \Modules\Service\Transformers\ServicePackTransformer::class,
        \Modules\Service\Models\ServiceCombo::class => \Modules\Service\Transformers\ServiceComboTransformer::class,
        \Modules\Service\Models\ServicePackPrice::class => \Modules\Service\Transformers\ServicePackPriceTransformer::class,
    ],

    /*
     * The transformer finder list
     */
    'transformer_finders' => [
    ],

    /*
     * Map model to external transformer
     */
    'external_transformers' => [
        \App\Base\Validator::class => \Modules\App\Transformers\ValidatorTransformer::class,
        \Modules\Merchant\Models\Merchant::class => \Modules\Merchant\ExternalTransformers\MerchantTransformer::class,
        \Modules\Order\Models\Order::class => \Modules\Merchant\ExternalTransformers\OrderTransformer::class,
        \Modules\Product\Models\Product::class => \Modules\Merchant\ExternalTransformers\ProductTransformer::class,
        \Modules\Product\Models\Sku::class => \Modules\Merchant\ExternalTransformers\SkuTransformer::class,
        \Modules\Stock\Models\Stock::class => \Modules\Merchant\ExternalTransformers\StockTransformer::class,
        \Modules\Warehouse\Models\Warehouse::class => \Modules\Merchant\ExternalTransformers\WarehouseTransformer::class,
        \Modules\Warehouse\Models\WarehouseArea::class => \Modules\Merchant\ExternalTransformers\WarehouseAreaTransformer::class,
        \Modules\Service\Models\Service::class => \Modules\Merchant\ExternalTransformers\ServiceTransformer::class,
        \Modules\ShippingPartner\Models\ShippingPartner::class => \Modules\Merchant\ExternalTransformers\ShippingPartnerTransformer::class,
        \Modules\FreightBill\Models\FreightBill::class => \Modules\Merchant\ExternalTransformers\FreightBillTransformer::class,

    ],

    /*
     * The external transformer finder list
     */
    'external_transformer_finders' => [
    ],
];
