<?php

namespace Modules\OrderPacking\Commands;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Order\Models\OrderStock;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\OrderPacking\Models\PickingSession;
use Modules\OrderPacking\Models\PickingSessionPiece;
use Modules\OrderPacking\Services\PickingSessionEvent;
use Modules\User\Models\User;
use Modules\Warehouse\Models\WarehouseArea;
use InvalidArgumentException;

class CreatingPickingSession
{
    /**
     * @var WarehouseArea
     */
    private $warehouseArea;
    /**
     * @var Collection
     */
    private $orderPackings;
    /**
     * @var User
     */
    private $user;

    public function __construct(WarehouseArea $warehouseArea, Collection $orderPackings, User $user)
    {
        $this->warehouseArea = $warehouseArea;
        $this->orderPackings = $orderPackings;
        $this->user          = $user;
    }

    /**
     * @return PickingSession
     */
    public function handle()
    {
        return DB::transaction(function () {
            /**
             * Tạo phiên nhặt hàng
             */
            /** @var PickingSession $pickingSession */
            $pickingSession = $this->createPickingSession();
            $pickingSession->logActivity(PickingSessionEvent::CREATE_PICKING_SESSION, $this->user);
            /**
             * Từ phiên nhặt hàng tạo ra các lượt nhặt hàng
             */
            $this->createPickingSessionPieces($pickingSession->refresh());
            return $pickingSession;
        });
    }

    /**
     * @return PickingSession|null
     */
    private function createPickingSession()
    {
        $lock           = Cache::lock($this->user->username . $this->warehouseArea->code, 60);
        $pickingSession = null;
        if ($lock->get()) {
            $pickingSession = PickingSession::create(
                [
                    'tenant_id' => $this->user->tenant_id,
                    'warehouse_id' => $this->warehouseArea->warehouse->id,
                    'warehouse_area_id' => $this->warehouseArea->id,
                    'picker_id' => $this->user->id,
                    'order_quantity' => $this->orderPackings->count(),
                    'order_packed_quantity' => 0
                ]
            );
        }

        if (empty($pickingSession)) {
            throw new InvalidArgumentException("Move too fast, Claim!");
        }

        return $pickingSession;
    }

    private function createPickingSessionPieces(PickingSession $pickingSession)
    {
        $warehouseAreas = $this->warehouseArea->warehouse->warehouseAreas->sortBy('picking_rank');
        $i              = 0;
        /** @var WarehouseArea $warehouseArea */
        foreach ($warehouseAreas as $warehouseArea) {
            /**
             * @var int $key
             * @var OrderPacking $orderPacking
             */
            foreach ($this->orderPackings as $key => $orderPacking) {
                $orderPacking->picking_session_id = $pickingSession->id;
                $orderPacking->save();
                $orderStocks = $orderPacking->order->orderStocks->where('warehouse_area_id', $warehouseArea->id);
                if ($orderStocks->count()) {
                    /** @var OrderStock $orderStock */
                    foreach ($orderStocks as $orderStock) {
                        PickingSessionPiece::updateOrCreate(
                            [
                                'tenant_id' => $this->user->tenant_id,
                                'picking_session_id' => $pickingSession->id,
                                'order_packing_id' => $orderPacking->id,
                                'warehouse_area_id' => $warehouseArea->id,
                                'sku_id' => $orderStock->sku_id,

                            ],
                            [
                                'order_id' => $orderPacking->order->id,
                                'warehouse_id' => $warehouseArea->warehouse->id,
                                'quantity' => $orderStock->quantity,
                                'ranking' => $i,
                                'ranking_order' => ($key + 1),
                            ]
                        );
                        $i++;
                    }
                }
            }
        }
    }
}
