<?php

namespace Modules\OrderPacking\Jobs;

use App\Base\Job;
use Illuminate\Database\Eloquent\Collection;
use Modules\Order\Services\OrderEvent;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Service;
use Modules\User\Models\User;
use Gobiz\Log\LogService;

class GetListOrderPackingJob extends Job
{
    const ACTION_CREATE_TRACKING_NO = 'create_tracking_no';
    const ACTION_CANCEL_TRACKING_NO = 'cancel_tracking_no';

    const ACTION_ADD_WAREHOUSE_AREA    = 'add_warehouse_area';
    const ACTION_REMOVE_WAREHOUSE_AREA = 'remove_warehouse_area';

    const ACTION_ADD_PRIORITY = 'add_priority';

    public $queue = 'create_tracking_no';
    protected $filter = [];
    /**
     * @var int
     */
    protected $batch = 100;

    /**
     * @var int
     */
    protected $creatorId;

    /**
     * @var User
     */
    protected $creator;

    /**
     * @var string
     */
    protected $action;

    /**
     * @var string
     */
    protected $pickupType;

    /**
     * GetListOrderPackingJob constructor.
     * @param array $filter
     * @param $creatorId
     * @param $action
     * @param null $pickupType
     */
    public function __construct(array $filter, $creatorId, $action, $pickupType = null)
    {
        $this->filter     = $filter;
        $this->creatorId  = $creatorId;
        $this->action     = $action;
        $this->pickupType = $pickupType;
    }


    /**
     * @param $message
     * @param array $context
     */
    protected function log($message, $context = [])
    {
        LogService::logger('list_order_packing_job')->info($message, $context);
    }


    public function handle()
    {
        $this->creator              = User::find($this->creatorId);
        $this->filter['exportData'] = true;

        $query = Service::orderPacking()->listing($this->filter);

        $query->chunk($this->batch, function (Collection $orderPackings) {
            $orderPackings->map(function (OrderPacking $orderPacking) {

                $this->log('start ' . $this->action . ' - ' . $orderPacking->id, $this->filter);

                switch ($this->action) {
                    case self::ACTION_CREATE_TRACKING_NO:
                    {
                        dispatch(new CreateTrackingNoJob($orderPacking->id, $this->creatorId, $this->pickupType));
                        break;
                    }
                    case self::ACTION_CANCEL_TRACKING_NO:
                    {
                        dispatch(new CancelTrackingNoJob($orderPacking->id, $this->creatorId));
                        break;
                    }
                    case self::ACTION_ADD_WAREHOUSE_AREA:
                    {
                        $order = $orderPacking->order;
                        if ($order->canAddWarehouseArea()) {
                            Service::order()->autoInspection($order, $this->creator);
                            $order->logActivity(OrderEvent::ADD_WAREHOUSE_AREA, $this->creator);
                        }
                        break;
                    }
                    case self::ACTION_REMOVE_WAREHOUSE_AREA:
                    {
                        $order = $orderPacking->order;
                        if ($order->canRemoveWarehouseArea()) {
                            Service::order()->removeStockOrder($order, $this->creator);
                        }
                        break;
                    }
                    case self::ACTION_ADD_PRIORITY:
                    {
                        $order = $orderPacking->order;
                        if ($order->canAddPriority()) {
                            $order->priority = true;
                            $order->save();

                            $orderPacking->priority = true;
                            $orderPacking->save();

                            $order->logActivity(OrderEvent::ADD_PRIORITY, $this->creator);
                        }
                        break;
                    }
                }
            });
        });
    }
}
