<?php

namespace Modules\OrderPacking\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Order\Models\OrderStock;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\OrderPacking\Models\PickingSession;
use Modules\OrderPacking\Services\PickingSessionEvent;
use Modules\User\Models\User;

class PickedPickingSession
{
    /**
     * @var PickingSession
     */
    private $pickingSession;
    /**
     * @var User
     */
    private $user;

    public function __construct(PickingSession $pickingSession, User $user)
    {
        $this->pickingSession = $pickingSession;
        $this->user           = $user;
    }

    /**
     * @return PickingSession
     */
    public function handle()
    {
        return DB::transaction(function () {
            $this->pickingSession->is_picked = true;
            $this->pickingSession->save();

            /**
             * Bỏ đánh dấu những YCĐH chưa nhặt hàng xong khỏi phiên nhặt hàngtha
             * Chuyển trạng thái những YCĐH đã nhặt hàng xong sang "chờ đóng gói"
             */
            /** @var OrderPacking $orderPacking */
            foreach ($this->pickingSession->orderPackings as $orderPacking) {
                $orderStocks = $orderPacking->order->orderStocks;
                /** @var OrderStock $orderStock */
                foreach ($orderStocks as $orderStock) {
                    if ($orderStock->warehouse_area_id != $this->pickingSession->warehouse_area_id) {
                        $orderPacking->picking_session_id = 0;
                    }
                }
                if (!$orderPacking->picking_session_id) {
                    $orderPacking->save();
                    continue;
                }
                if ($orderPacking->canChangeStatus(OrderPacking::STATUS_WAITING_PACKING)) {
                    $orderPacking->changeStatus(OrderPacking::STATUS_WAITING_PACKING, $this->user);
                    $orderPacking->pickup_truck_id = $this->pickingSession->warehouse_area_id;
                    $orderPacking->save();
                }
            }
            $this->pickingSession->logActivity(PickingSessionEvent::PICKED, $this->user, ['piece_id' => $this->pickingSession->id]);

            return $this->pickingSession->refresh();
        });
    }
}
