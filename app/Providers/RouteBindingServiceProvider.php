<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use mmghv\LumenRouteBinding\RouteBindingServiceProvider as BaseServiceProvider;
use Modules\DeliveryNote\Models\DeliveryNote;
use Modules\Document\Models\Document;
use Modules\ImportHistory\Models\ImportHistory;
use Modules\InvalidOrder\Models\InvalidOrder;
use Modules\Order\Models\Order;
use Modules\Merchant\Models\Merchant;
use Modules\Category\Models\Category;
use Modules\OrderPacking\Models\PickingSession;
use Modules\OrderPacking\Models\PickingSessionPiece;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceCombo;
use Modules\Service\Models\ServicePack;
use Modules\Service\Models\ServicePrice;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Stock\Models\Stock;
use Modules\Store\Models\Store;
use Modules\Store\Models\StoreSku;
use Modules\Supplier\Models\Supplier;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;
use Modules\User\Models\User;

class RouteBindingServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        $models = [
            'product' => Product::class,
            'sku' => Sku::class,
            'merchant' => Merchant::class,
            'order' => Order::class,
            'warehouse' => Warehouse::class,
            'warehouseArea' => WarehouseArea::class,
            'importHistory' => ImportHistory::class,
            'category' => Category::class,
            'supplier' => Supplier::class,
            'user' => User::class,
            'deliveryNote' => DeliveryNote::class,
            'store' => [
                'class' => Store::class,
                'belong_to_merchant' => true,
            ],
            'invalidOrder' => InvalidOrder::class,
            'storeSku' => StoreSku::class,
            'shippingPartner' => ShippingPartner::class,
            'stock' => Stock::class,
            'service' => Service::class,
            'servicePrice' => ServicePrice::class,
            'pickingSession' => PickingSession::class,
            'pickingSessionPiece' => PickingSessionPiece::class,
            'purchasingPackage' => PurchasingPackage::class,
            'documentDeliveryComparison' => Document::class,
            'servicePack' => ServicePack::class,
            'serviceCombo' => ServiceCombo::class,
        ];

        foreach ($models as $key => $model) {
            $model = is_string($model) ? ['class' => $model] : $model;
            $this->bindModel($key, $model);
        }
    }

    /**
     * @param string $key
     * @param array $model
     */
    protected function bindModel($key, $model)
    {
        $this->binder->bind($key, function ($id) use ($model) {
            /**
             * @var Model $instance
             */
            $instance       = new $model['class'];
            $isMerchantArea = Str::startsWith(request()->path(), 'merchant/');

            $where = [
                $instance->getKeyName() => $id,
                'tenant_id' => Auth::user()->tenant_id,
            ];

            if ($isMerchantArea && !empty($model['belong_to_merchant'])) {
                $where['merchant_id'] = Auth::user()->merchant->id;
            }

            return $instance->query()->where($where)->firstOrFail();
        });
    }
}
