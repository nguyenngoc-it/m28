<?php

namespace Modules\Order\Commands;

use Gobiz\Workflow\WorkflowException;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderSku;
use Modules\Service;
use Modules\Stock\Models\Stock;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;

class AutoInspectionWithWarehouse
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var Warehouse
     */
    protected $warehouse;

    /**
     * @var User|null
     */
    protected $creator = null;

    /**
     * AutoInspectionWithWarehouse constructor.
     * @param Order $order
     * @param Warehouse $warehouse
     * @param User $creator
     */
    public function __construct(Order $order, Warehouse $warehouse, User $creator)
    {
        $this->order     = $order;
        $this->warehouse = $warehouse;
        $this->creator   = $creator;
    }

    /**
     * @return bool
     * @throws WorkflowException
     */
    public function handle()
    {
        /**
         * Nếu toàn bộ skus trên đơn đều đã có trong orderStocks thì đơn này đã chọn full kho xuất rồi
         */
        if (
            $this->order->orderStocks->pluck('sku_id')->unique()->count()
            == $this->order->orderSkus->pluck('sku_id')->unique()->count()) {
            /**
             * Nếu hoàn thành gán được kho cho tất cả skus sẽ chuyển trạng thái chờ xác nhận
             */
            if ($this->order->canChangeStatus(Order::STATUS_WAITING_CONFIRM)) {
                $this->order->changeStatus(Order::STATUS_WAITING_CONFIRM, $this->creator);
            }
            return true;
        }

        /**
         * Kiểm tra và gán kho xuất cho từng sku, nếu có ít nhất 1 sku không gán được kho thì return false
         */
        /** @var OrderSku $orderSku */
        $grantFullWarehouse = true;

        $orderSkuDatas = [];

        foreach ($this->order->orderSkus as $orderSku) {
            if (isset($orderSkuDatas[$orderSku->sku_id])) {
                $orderSkuCur = $orderSkuDatas[$orderSku->sku_id];
                $orderSkuCur->quantiy += $orderSku->quantity;
                $orderSkuDatas[$orderSku->sku_id] = $orderSkuCur;
            } else {
                $orderSkuDatas[$orderSku->sku_id] = $orderSku;
            }
        }

        foreach ($orderSkuDatas as $orderSku) {
            if (!$this->grantWarehouseAreaForSku($orderSku)) {
                $grantFullWarehouse = false;
            }
        }

        /**
         * Nếu hoàn thành gán được kho cho tất cả skus sẽ chuyển trạng thái chờ xác nhận
         */
        if ($grantFullWarehouse && $this->order->canChangeStatus(Order::STATUS_WAITING_CONFIRM)) {
            $this->order->changeStatus(Order::STATUS_WAITING_CONFIRM, $this->creator);
        }

        return $grantFullWarehouse;
    }

    /**
     * Chọn kho xuất cho 1 sku của đơn (chỉ tự động xuất nếu sku có đủ số lượng nằm ở 1 kho và duy nhất 1 vị trí kho đó)
     * Nếu sku thuộc sản phẩm dropship thì luôn chọn được kho xuất không quan tâm đến số lượng
     *
     * @param OrderSku $orderSku
     *
     * @return boolean
     */
    protected function grantWarehouseAreaForSku(OrderSku $orderSku)
    {
        $stockQuery = Stock::query()->select('stocks.*')->where('stocks.sku_id', $orderSku->sku_id)
            ->where('stocks.warehouse_id', $this->warehouse->id);
        if (!$orderSku->sku->product->dropship) {
            $stockQuery->where('stocks.quantity', '>=', $orderSku->quantity);
        }
        $stockQuery->join('warehouse_areas', 'stocks.warehouse_area_id', 'warehouse_areas.id');
        $stock = $stockQuery->orderBy('warehouse_areas.code')->first();
        if (!$stock instanceof Stock) {
            return false;
        }
        Service::order()->createOrderStock(
            $this->order,
            $stock,
            $orderSku->quantity,
            $this->creator
        );
        return true;
    }
}
