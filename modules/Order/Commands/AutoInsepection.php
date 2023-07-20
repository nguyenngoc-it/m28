<?php

namespace Modules\Order\Commands;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Order\Events\OrderInspected;
use Modules\Order\Events\OrderSkusCompletedBatch;
use Modules\Order\Events\OrderSkusUpdatedBatch;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderSku;
use Modules\Service;
use Modules\Stock\Models\Stock;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;
use Gobiz\Log\LogService;

class AutoInsepection
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var User|null
     */
    protected $creator = null;


    protected $logger = null;

    /**
     * AutoInsepection constructor.
     * @param Order $order
     * @param User $creator
     */
    public function __construct(Order $order, User $creator)
    {
        $this->order   = $order;
        $this->creator = $creator;
        $this->logger  = LogService::logger("Auto-Insepection", [
            'context' => [],
        ]);
    }

    public function handle()
    {
        Service::locking()->execute(function () {
            $this->inspect();
        }, $this->order->tenant_id, "order.{$this->order->id}.inspect");
    }

    protected function inspect()
    {
        $countOrderStocks = $this->order->orderStocks->pluck('sku_id')->unique()->count();
        $countOrderSkus   = $this->order->orderSkus->pluck('sku_id')->unique()->count();
        if ($countOrderStocks && $countOrderStocks == $countOrderSkus) {
            $this->order->inspected = true;
            $this->order->save();
            return;
        }

        /**
         * Convert sang sku lô nếu đơn có sku lô cha
         */
        $this->convertParentSkutoChildren();

        /**
         * Nếu đơn đã chọn kho
         */
        if ($this->order->warehouse) {
            $this->autoByWarehouse($this->order->warehouse);
            return;
        }

        /**
         * Tìm ra kho xuất của đơn
         */
        $warehouse = $this->findExportWarehouse();
        if ($warehouse) {
            $this->autoByWarehouse($warehouse);
        }
    }

    /**
     * @return void
     */
    protected function convertParentSkuToChildren()
    {
        $updateFullBatch = true;
        DB::transaction(function () use (&$updateFullBatch) {
            /** @var OrderSku $orderSku */
            foreach ($this->order->orderSkus as $orderSku) {
                $sku = $orderSku->sku;
                if ($sku->is_batch) {
                    $orderSkuChildren = Service::sku()->findOrderSkuChildren($sku, $orderSku->quantity, $orderSku->price);
                    if ($orderSkuChildren) {
                        /**
                         * Xoá sku lô cha trên đơn
                         */
                        $orderSku->delete();
                        /**
                         * Tạo orderskus mới theo sku lô con
                         */
                        $this->order->orderSkus()->createMany($orderSkuChildren);

                        $childrenOfSkus = $sku->skuChildren->pluck('code', 'id')->all();
                        foreach ($orderSkuChildren as &$orderSkuChild) {
                            $orderSkuChild['sku_code'] = $childrenOfSkus[$orderSkuChild['sku_id']];
                        }
                        (new OrderSkusUpdatedBatch($this->order, $this->creator, Carbon::now(), $orderSkuChildren))->queue();
                    } else {
                        $updateFullBatch = false;
                    }
                }
            }
        });

        if ($updateFullBatch) {
            (new OrderSkusCompletedBatch($this->order, $this->creator, Carbon::now()))->queue();
        }

        $this->order->load('orderSkus');
    }

    /**
     * @param Warehouse $warehouse
     * @return void
     */
    protected function autoByWarehouse(Warehouse $warehouse)
    {
        /**
         * Kiểm tra và gán kho xuất cho từng sku, nếu có ít nhất 1 sku không gán được kho thì return false
         */
        /** @var OrderSku $orderSku */
        $grantFullWarehouse = true;
        $orderSkuDatas      = [];

        foreach ($this->order->orderSkus as $orderSku) {
            if (isset($orderSkuDatas[$orderSku->sku_id])) {
                $orderSkuCur                      = $orderSkuDatas[$orderSku->sku_id];
                $orderSkuCur->quantity            += $orderSku->quantity;
                $orderSkuDatas[$orderSku->sku_id] = $orderSkuCur;
            } else {
                $orderSkuDatas[$orderSku->sku_id] = $orderSku;
            }
        }

        foreach ($orderSkuDatas as $orderSku) {
            if (!$this->grantWarehouseAreaForSku($orderSku, $warehouse)) {
                $grantFullWarehouse = false;
            }
        }

        $this->logger->info('data_order_sku', $orderSkuDatas);

        if ($grantFullWarehouse) {
            $this->order->inspected = true;
            $this->order->save();
            (new OrderInspected($this->order, $this->creator))->queue();
        }
    }

    /**
     * Chọn vị trí trong kho sẽ lấy hàng
     * Nếu nhiều hơn 1 vị trí còn đủ sl thì lấy theo alphabel của mã vị trí
     *
     * @param OrderSku $orderSku
     *
     * @param Warehouse $warehouse
     * @return boolean
     */
    protected function grantWarehouseAreaForSku(OrderSku $orderSku, Warehouse $warehouse)
    {
        /**
         * Sku đã chọn vị trí rồi thì bỏ qua
         */
        if ($this->order->orderStocks->where('sku_id', $orderSku->sku_id)->first()) {
            return true;
        }

        $stockQuery = Stock::query()->select('stocks.*')->where('stocks.sku_id', $orderSku->sku_id)
            ->where('stocks.warehouse_id', $warehouse->id);
        if (!$orderSku->sku->product->dropship) {
            $stockQuery->where('stocks.quantity', '>=', $orderSku->quantity);
        }
        $stockQuery->join('warehouse_areas', 'stocks.warehouse_area_id', 'warehouse_areas.id');
        $stock = $stockQuery->orderBy('warehouse_areas.code')->first();
        if (!$stock instanceof Stock) {
            return false;
        }
        $orderStock = Service::order()->createOrderStock(
            $this->order,
            $stock,
            $orderSku->quantity,
            $this->creator
        );

        return (bool)$orderStock;
    }

    /**
     * Tìm kho xuất phù hợp cho đơn
     *
     * @return Warehouse
     */
    protected function findExportWarehouse()
    {
        $listCountryWarehouses       = Warehouse::query()->where([
            'tenant_id' => $this->order->tenant_id,
            'country_id' => $this->order->receiver_country_id
        ])->get();
        $listCountrySortedWarehouses = [];
        foreach ($listCountryWarehouses as $warehouse) {
            foreach ($this->order->orderSkus as $orderSku) {
                $stockQuery = Stock::query()->select('stocks.*')->where('stocks.sku_id', $orderSku->sku_id)
                    ->where('stocks.warehouse_id', $warehouse->id);
                if (!$orderSku->sku->product->dropship) {
                    $stockQuery->where('stocks.quantity', '>=', $orderSku->quantity);
                }
                if ($stockQuery->first()) {
                    $listCountrySortedWarehouses['_' . $warehouse->id][] = $orderSku->sku_id;
                }
            }
        }
        array_multisort(array_map('count', $listCountrySortedWarehouses), SORT_DESC, $listCountrySortedWarehouses);
        $maxCountSku        = 0;
        $i                  = 0;
        $maxCountWarehouses = [];
        foreach ($listCountrySortedWarehouses as $_warehouseId => $skus) {
            $i++;
            if ($i == 1) {
                $maxCountSku = count($skus);
            }
            if ($maxCountSku == count($skus)) {
                $maxCountWarehouses[] = $_warehouseId;
            }
        }
        sort($maxCountWarehouses);
        //dd($maxCountWarehouses);
        foreach ($maxCountWarehouses as $maxCountWarehouseId) {
            $maxCountWarehouseId = str_replace('_', '', $maxCountWarehouseId);
            //dd($maxCountWarehouseId);
            return Warehouse::find($maxCountWarehouseId);
        }

        return null;
    }
}
