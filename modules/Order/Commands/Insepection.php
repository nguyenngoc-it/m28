<?php

namespace Modules\Order\Commands;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Order\Events\OrderInspected;
use Modules\Order\Models\Order;
use Modules\Order\Services\OrderEvent;
use Modules\Service;
use Modules\Stock\Models\Stock;
use Modules\User\Models\User;
use InvalidArgumentException;


class Insepection
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var array
     */
    protected $orderStocks;

    /**
     * @var User|null
     */
    protected $creator = null;

    /**
     * Insepection constructor.
     * @param Order $order
     * @param array $inputs
     * @param User $creator
     */
    public function __construct(Order $order, array $inputs, User $creator)
    {
        $this->order       = $order;
        $this->orderStocks = Arr::get($inputs, 'order_stocks', []);
        $this->creator     = $creator;
    }

    /**
     * @return Order
     */
    public function handle()
    {
        return DB::transaction(function () {
            foreach ($this->orderStocks as $orderStock) {
                /** @var Stock|null $stock */
                $stock = Stock::query()->where([
                    'sku_id' => $orderStock['sku_id'],
                    'tenant_id' => $this->creator->tenant_id,
                    'warehouse_area_id' => $orderStock['warehouse_area_id'],
                ])->first();

                if (empty($stock)) {
                    throw new InvalidArgumentException("Can't find stock by sku_id " . $orderStock['sku_id']);
                }

                /**
                 * Bỏ qua nếu sku đã chọn được kho xuất
                 */
                if (in_array($orderStock['sku_id'], $this->order->orderStocks->pluck('sku_id')->all())) {
                    continue;
                }

                Service::order()->createOrderStock(
                    $this->order,
                    $stock,
                    $orderStock['quantity'],
                    $this->creator
                );
            }

            $this->order->logActivity(OrderEvent::INSPECTION, $this->creator);

            $this->order->inspected = true;
            $this->order->save();
            (new OrderInspected($this->order, $this->creator))->queue();
            /**
             * Nếu chọn được vị trí kho xuất cập nhật lại kho vận hành của YCĐH
             */
            if (($orderWarehouse = $this->order->getWarehouseStock()) && $this->order->inspected) {
                $orderPacking = $this->order->orderPacking;
                if ($orderPacking) {
                    $orderPacking->warehouse_id = $orderWarehouse->id;
                    $orderPacking->save();
                }
            }
            return $this->order->refresh();
        });
    }
}
