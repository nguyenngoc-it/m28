<?php

namespace Modules\WarehouseStock\Jobs;

use App\Base\Job;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Database\Eloquent\Builder;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderSku;
use Modules\Order\Services\StatusOrder;
use Modules\Product\Models\Sku;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\PurchasingPackage\Models\PurchasingPackageItem;
use Modules\Service;
use Modules\Stock\Models\Stock;
use Modules\Tenant\Models\TenantSetting;
use Modules\Warehouse\Models\Warehouse;
use Gobiz\Log\LogService;

class CalculateWarehouseStockJob extends Job implements ShouldBeUnique
{
    public $connection = 'redis';
    public $queue = 'calculate_warehouse_stock';

    /**
     * @var integer
     */
    protected $skuId;

    /**
     * @var integer
     */
    protected $warehouseId;

    /**
     * @var Sku
     */
    protected $sku;

    /**
     * @var Warehouse
     */
    protected $warehouse;

    /**
     * CalculateWarehouseStockJob constructor.
     * @param $skuId
     * @param $warehouseId
     */
    public function __construct($skuId, $warehouseId)
    {
        $this->skuId       = $skuId;
        $this->warehouseId = $warehouseId;
    }

    /**
     * @return string
     */
    public function uniqueId()
    {
        return 'warehouse_stock_' . $this->skuId . '_' . $this->warehouseId;
    }

    /**
     * @param $message
     * @param array $context
     */
    protected function log($message, array $context = [])
    {
        LogService::logger('calculate_warehouse_stock')->info($message, $context);
    }

    /**
     * @return bool
     */
    protected function validateData()
    {
        $this->sku       = Sku::find($this->skuId);
        $this->warehouse = Warehouse::find($this->warehouseId);
        if (!$this->sku instanceof Sku || !$this->warehouse instanceof Warehouse) {
            $this->log('sku ' . $this->skuId . ' or warehouse ' . $this->warehouseId . ' empty ');
            return false;
        }

        return true;
    }

    /**
     * Lấy tổng số luọng chờ xuất
     * @return int
     */
    protected function getPackingQuantity()
    {
        return OrderSku::query()->select(['order_skus.order_id', 'order_skus.quantity'])
            ->leftJoin('orders', 'order_skus.order_id', '=', 'orders.id')
            ->leftJoin('order_stocks', 'order_skus.order_id', '=', 'order_stocks.order_id')
            ->whereIn('orders.status', StatusOrder::getBeforeStatus(Order::STATUS_DELIVERING))
            ->where('order_skus.sku_id', $this->sku->id)
            ->where(function (Builder $builder) {
                $builder->where('orders.warehouse_id', $this->warehouse->id)
                    ->orWhere('order_stocks.warehouse_id', $this->warehouse->id);
            })
            ->groupBy(['order_skus.order_id', 'order_skus.quantity'])->get()
            ->sum('quantity');
    }


    /**
     * tổng hợp số lượng của SKU từ những kiện hàng chưa nhập hàng
     * @return int
     */
    protected function getPurchasingQuantity()
    {
        return PurchasingPackageItem::query()
            ->join('purchasing_packages', 'purchasing_package_items.purchasing_package_id', '=', 'purchasing_packages.id')
            ->where('purchasing_packages.destination_warehouse_id', $this->warehouse->id)
            ->whereNotIn('purchasing_packages.status', [PurchasingPackage::STATUS_IMPORTED, PurchasingPackage::STATUS_CANCELED])
            ->where('purchasing_package_items.sku_id', $this->sku->id)
            ->sum('purchasing_package_items.quantity');
    }

    /**
     * @return void
     */
    public function handle()
    {
        $this->log('start sku ' . $this->skuId . ' - warehouse_id ' . $this->warehouseId);
        if (!$this->validateData()) {
            return;
        }
        $stocks             = Stock::query()->where(['sku_id' => $this->skuId, 'warehouse_id' => $this->warehouseId])->get();
        $tenant             = $this->sku->tenant;
        $purchasingQuantity = $this->getPurchasingQuantity();
        $packingQuantity    = $this->getPackingQuantity();

        $this->log('sku ' . $this->skuId . ' - warehouse_id ' . $this->warehouseId, [
            'sku_code' => $this->sku->code,
            'purchasing_quantity' => $purchasingQuantity,
            'packing_quantity' => $packingQuantity,
        ]);

        $warehouseStock                      = Service::warehouseStock()->make($this->sku, $this->warehouse);
        $warehouseStock->quantity            = $stocks->sum('quantity');
        $warehouseStock->real_quantity       = $stocks->sum('real_quantity');
        $warehouseStock->sku_status          = $this->sku->status;
        $warehouseStock->purchasing_quantity = $purchasingQuantity;
        $warehouseStock->packing_quantity    = $packingQuantity;
        $warehouseStock->saleable_quantity   = $warehouseStock->quantity + $warehouseStock->purchasing_quantity;
        $minQuantity                         = ($warehouseStock->min_quantity !== null) ? $warehouseStock->min_quantity : $tenant->getSetting(TenantSetting::SKU_MIN_STOCK);
        $warehouseStock->out_of_stock        = $warehouseStock->saleable_quantity < intval($minQuantity);
        $warehouseStock->save();
    }
}
