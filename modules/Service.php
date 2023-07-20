<?php

namespace Modules;

use Modules\App\Services\AppServiceInterface;
use Modules\Auth\Services\AuthServiceInterface;
use Modules\DeliveryNote\Services\DeliveryNoteServiceInterface;
use Modules\Document\Services\DocumentDeliveryComparisonServiceInterface;
use Modules\Document\Services\DocumentExportingServiceInterface;
use Modules\Document\Services\DocumentPackingServiceInterface;
use Modules\Document\Services\DocumentServiceInterface;
use Modules\Document\Services\DocumentImportingServiceInterface;
use Modules\Document\Services\DocumentSkuInventoryServiceInterface;
use Modules\EventBridge\Services\EventBridgeServiceInterface;
use Modules\FreightBill\Services\FreightBillServiceInterface;
use Modules\InvalidOrder\Services\InvalidOrderServiceInterface;
use Modules\Lazada\Services\LazadaServiceInterface;
use Modules\Marketplace\Services\MarketplaceServiceInterface;
use Modules\Order\Services\OrderServiceInterface;
use Modules\OrderExporting\Services\OrderExportingServiceInterface;
use Modules\OrderPacking\Services\OrderPackingServiceInterface;
use Modules\Product\Services\SkuServiceInterface;
use Modules\PurchasingManager\Services\PurchasingManagerServiceInterface;
use Modules\PurchasingOrder\Services\PurchasingOrderServiceInterface;
use Modules\Service\Services\ServiceServiceInterface;
use Modules\Shopee\Services\ShopeeServiceInterface;
use Modules\KiotViet\Services\KiotVietServiceInterface;
use Modules\SupplierTransaction\Service\SupplierTransactionInterface;
use Modules\Tiki\Services\TikiServiceInterface;
use Modules\TikTokShop\Services\TikTokShopServiceInterface;
use Modules\Stock\Services\StockServiceInterface;
use Modules\Store\Services\StoreServiceInterface;
use Modules\Tenant\Services\TenantServiceInterface;
use Modules\Topship\Services\TopshipServiceInterface;
use Modules\Transaction\Services\TransactionServiceInterface;
use Modules\User\Services\UserServiceInterface;
use Modules\Product\Services\ProductServiceInterface;
use Modules\Warehouse\Services\WarehouseServiceInterface;
use Modules\Location\Services\LocationServiceInterface;
use Modules\Merchant\Services\MerchantServiceInterface;
use Modules\ImportHistory\Services\ImportHistoryServiceInterface;
use Modules\Currency\Services\CurrencyServiceInterface;
use Modules\Category\Services\CategoryServiceInterface;
use Modules\ShopBase\Services\ShopBaseServiceInterface;
use App\Services\Log\LogServiceInterface as AppLogServiceInterface;
use Modules\ShippingPartner\Services\ShippingPartnerServiceInterface;
use Modules\WarehouseStock\Services\WarehouseStockServiceInterface;
use Modules\PurchasingPackage\Services\PurchasingPackageServiceInterface;
use Modules\Document\Services\DocumentFreightBillInventoryServiceInterface;
use Modules\Locking\Services\LockingServiceInterface;
use Modules\ShopBaseUs\Services\ShopBaseUsServiceInterface;
use Modules\Sapo\Services\SapoServiceInterface;
use Modules\Supplier\Services\SupplierServiceInterface;
use Modules\Document\Services\DocumentSupplierTransactionServiceInterface;

class Service
{
    /**
     * @return AppServiceInterface
     */
    public static function app()
    {
        return app(AppServiceInterface::class);
    }

    /**
     * @return AuthServiceInterface
     */
    public static function auth()
    {
        return app(AuthServiceInterface::class);
    }

    /**
     * @return TenantServiceInterface
     */
    public static function tenant()
    {
        return app(TenantServiceInterface::class);
    }

    /**
     * @return TransactionServiceInterface
     */
    public static function transaction()
    {
        return app(TransactionServiceInterface::class);
    }

    /**
     * @return UserServiceInterface
     */
    public static function user()
    {
        return app(UserServiceInterface::class);
    }

    /**
     * @return OrderServiceInterface
     */
    public static function order()
    {
        return app(OrderServiceInterface::class);
    }

    /**
     * @return OrderPackingServiceInterface
     */
    public static function orderPacking()
    {
        return app(OrderPackingServiceInterface::class);
    }

    /**
     * @return OrderExportingServiceInterface
     */
    public static function orderExporting()
    {
        return app(OrderExportingServiceInterface::class);
    }

    /**
     * @return ProductServiceInterface
     */
    public static function product()
    {
        return app(ProductServiceInterface::class);
    }

    /**
     * @return SkuServiceInterface
     */
    public static function sku()
    {
        return app(SkuServiceInterface::class);
    }

    /**
     * @return StockServiceInterface
     */
    public static function stock()
    {
        return app(StockServiceInterface::class);
    }

    /**
     * @return WarehouseServiceInterface
     */
    public static function warehouse()
    {
        return app(WarehouseServiceInterface::class);
    }

    /**
     * @return LocationServiceInterface
     */
    public static function location()
    {
        return app(LocationServiceInterface::class);
    }

    /**
     * @return ImportHistoryServiceInterface
     */
    public static function importHistory()
    {
        return app(ImportHistoryServiceInterface::class);
    }

    /**
     * @return MerchantServiceInterface
     */
    public static function merchant()
    {
        return app(MerchantServiceInterface::class);
    }

    /**
     * @return CurrencyServiceInterface
     */
    public static function currency()
    {
        return app(CurrencyServiceInterface::class);
    }

    /**
     * @return CategoryServiceInterface
     */
    public static function category()
    {
        return app(CategoryServiceInterface::class);
    }

    /**
     * @return DeliveryNoteServiceInterface
     */
    public static function deliveryNote()
    {
        return app(DeliveryNoteServiceInterface::class);
    }

    /**
     * @return ShopBaseServiceInterface
     */
    public static function shopBase()
    {
        return app(ShopBaseServiceInterface::class);
    }

    /**
     * @return AppLogServiceInterface
     */
    public static function appLog()
    {
        return app(AppLogServiceInterface::class);
    }

    /**
     * @return DocumentServiceInterface
     */
    public static function document()
    {
        return app(DocumentServiceInterface::class);
    }

    /**
     * @return ShippingPartnerServiceInterface
     */
    public static function shippingPartner()
    {
        return app(ShippingPartnerServiceInterface::class);
    }


    /**
     * @return DocumentExportingServiceInterface
     */
    public static function documentExporting()
    {
        return app(DocumentExportingServiceInterface::class);
    }

    /**
     * @return DocumentPackingServiceInterface
     */
    public static function documentPacking()
    {
        return app(DocumentPackingServiceInterface::class);
    }

    /**
     * @return DocumentImportingServiceInterface
     */
    public static function documentImporting()
    {
        return app(DocumentImportingServiceInterface::class);
    }

    /**
     * @return PurchasingManagerServiceInterface
     */
    public static function purchasingManager()
    {
        return app(PurchasingManagerServiceInterface::class);
    }

    /**
     * @return PurchasingOrderServiceInterface
     */
    public static function purchasingOrder()
    {
        return app(PurchasingOrderServiceInterface::class);
    }

    /**
     * @return MarketplaceServiceInterface
     */
    public static function marketplace()
    {
        return app(MarketplaceServiceInterface::class);
    }

    /**
     * @return StoreServiceInterface
     */
    public static function store()
    {
        return app(StoreServiceInterface::class);
    }

    /**
     * @return ShopeeServiceInterface
     */
    public static function shopee()
    {
        return app(ShopeeServiceInterface::class);
    }

    /**
     * @return LazadaServiceInterface
     */
    public static function lazada()
    {
        return app(LazadaServiceInterface::class);
    }

    /**
     * @return TikiServiceInterface
     */
    public static function tiki()
    {
        return app(TikiServiceInterface::class);
    }

    /**
     * @return TikTokShopServiceInterface
     */
    public static function tikTokShop()
    {
        return app(TikTokShopServiceInterface::class);
    }

    /**
     * @return ShopBaseUsServiceInterface
     */
    public static function shopBaseUs()
    {
        return app(ShopBaseUsServiceInterface::class);
    }

    /**
     * @return KiotVietServiceInterface
     */
    public static function kiotviet()
    {
        return app(KiotVietServiceInterface::class);
    }

    /**
     * @return SapoServiceInterface
     */
    public static function sapo()
    {
        return app(SapoServiceInterface::class);
    }

    /**
     * @return WarehouseStockServiceInterface
     */
    public static function warehouseStock()
    {
        return app(WarehouseStockServiceInterface::class);
    }

    /**
     * @return FreightBillServiceInterface
     */
    public static function freightBill()
    {
        return app(FreightBillServiceInterface::class);
    }

    /**
     * @return InvalidOrderServiceInterface
     */
    public static function invalidOrder()
    {
        return app(InvalidOrderServiceInterface::class);
    }

    /**
     * @return DocumentSkuInventoryServiceInterface
     */
    public static function documentSkuInventory()
    {
        return app(DocumentSkuInventoryServiceInterface::class);
    }

    /**
     * @return PurchasingPackageServiceInterface
     */
    public static function purchasingPackage()
    {
        return app(PurchasingPackageServiceInterface::class);
    }

    /**
     * @return DocumentFreightBillInventoryServiceInterface
     */
    public static function documentFreightBillInventory()
    {
        return app(DocumentFreightBillInventoryServiceInterface::class);
    }

    /**
     * @return DocumentDeliveryComparisonServiceInterface
     */
    public static function documentDeliveryComparison()
    {
        return app(DocumentDeliveryComparisonServiceInterface::class);
    }

    /**
     * @return ServiceServiceInterface
     */
    public static function service()
    {
        return app(ServiceServiceInterface::class);
    }

    /**
     * @return TopshipServiceInterface
     */
    public static function topship()
    {
        return app(TopshipServiceInterface::class);
    }

    /**
     * @return LockingServiceInterface
     */
    public static function locking()
    {
        return app(LockingServiceInterface::class);
    }

    /**
     * @return EventBridgeServiceInterface
     */
    public static function eventBridge()
    {
        return app(EventBridgeServiceInterface::class);
    }

    /**
     * @return SupplierServiceInterface
     */
    public static function supplier()
    {
        return app(SupplierServiceInterface::class);
    }

    public static function supplierTransaction()
    {
        return app(SupplierTransactionInterface::class);
    }

    /**
     * @return DocumentSupplierTransactionServiceInterface
     */
    public static function documentSupplierTransaction()
    {
        return app(DocumentSupplierTransactionServiceInterface::class);
    }
}
