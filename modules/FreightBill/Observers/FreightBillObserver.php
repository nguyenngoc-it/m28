<?php

namespace Modules\FreightBill\Observers;

use Gobiz\Workflow\WorkflowException;
use Modules\FreightBill\Services\FreightBillEvent;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Events\OrderShippingFinancialStatusChanged;
use Modules\Order\Models\Order;
use Modules\OrderIntegration\PublicEvents\FreightBillUpdated;
use Gobiz\Log\LogService;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Service;

class FreightBillObserver
{
    public $afterCommit = true;

    /**
     * Handle to the FreightBill "created" event.
     *
     * @param FreightBill $freightBill
     * @return void
     * @throws WorkflowException
     */
    public function created(FreightBill $freightBill)
    {
        (new OrderShippingFinancialStatusChanged($freightBill->order, Order::SFS_INIT))->queue();
        $freightBill = $this->updateShippingPartner($freightBill);
        (new FreightBillUpdated($freightBill, FreightBillEvent::CREATED))->publish();

        /**
         * Có vận đơn thì YCĐH chuyển chờ xử lý
         */
        $orderPacking = $freightBill->orderPacking;
        if ($orderPacking && $orderPacking->canChangeStatus(OrderPacking::STATUS_WAITING_PICKING)) {
            $orderPacking->changeStatus(OrderPacking::STATUS_WAITING_PICKING, Service::user()->getSystemUserDefault());
        }
    }

    /**
     * Handle the FreightBill "updated" event.
     *
     * @param FreightBill $freightBill
     * @return void
     */
    public function updated(FreightBill $freightBill)
    {
        $freightBill = $this->updateShippingPartner($freightBill);

        $changed = $freightBill->getChanges();
        if (
            isset($changed['status'])
        ) {
            (new FreightBillUpdated($freightBill, FreightBillEvent::CHANGE_STATUS))->publish();
        }
    }

    /**
     * 1 số trường hợp vận đơn không có dvvc nhưng YCDH lại có nên cần update lại
     * @param FreightBill $freightBill
     * @return FreightBill
     */
    protected function updateShippingPartner(FreightBill $freightBill)
    {
        if (
            empty($freightBill->shipping_partner_id) &&
            !empty($freightBill->orderPacking->shipping_partner_id)
        ) {
            LogService::logger('freight_update_shipping_partner')->info('change shipping_partner  ' . $freightBill->shipping_partner_id);

            $freightBill->shipping_partner_id = $freightBill->orderPacking->shipping_partner_id;
            $freightBill->save();
        }

        return $freightBill;
    }
}
