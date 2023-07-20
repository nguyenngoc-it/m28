<?php

namespace Modules\Order\Middleware;

use Closure;
use Gobiz\Log\LogService;
use Gobiz\Workflow\ApplyTransitionCommand;
use Gobiz\Workflow\WorkflowException;
use Gobiz\Workflow\WorkflowMiddlewareInterface;
use Modules\Order\Events\OrderShippingFinancialStatusChanged;
use Modules\Order\Models\Order;
use Modules\OrderPacking\Jobs\CreatingOrderPackingJob;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\ShippingPartner\Services\ExpectedTransportingPrice\ExpectedTransportingPriceException;
use Modules\Stock\Jobs\UpdateTemporaryStockJob;
use Modules\User\Models\User;

/**
 * Tạo yêu cầu đóng hàng khi đơn đã chọn được hết sản phẩm
 * Đơn chuyển trạng thái chờ đóng hàng (WAITING_PACKING)
 *
 * Class PrepareOrderChangeStatus
 * @package Modules\Order\Middleware
 */
class PrepareOrderChangeStatus implements WorkflowMiddlewareInterface
{
    /**
     * @param ApplyTransitionCommand $command
     * @param Closure $next
     * @return mixed
     * @throws WorkflowException
     */
    public function handle(ApplyTransitionCommand $command, Closure $next)
    {
        /**
         * @var Order $order
         */
        $order   = $command->subject;
        $creator = $command->getPayload('creator');
        $res     = $next($command);
        LogService::logger('PrepareOrderChangeStatus')->info($order->code . '-' . $order->status);

        /**
         * Đơn chuyển trạng thái "chờ xử lý" sẽ tạo ra YCĐH
         */
        if ($order->status == Order::STATUS_WAITING_PROCESSING && !$order->orderPacking) {
            dispatch(new CreatingOrderPackingJob($order->id));
        }

        /**
         * Huỷ đơn
         */
        if ($order->status == Order::STATUS_CANCELED) {
            $this->cancelOrderPacking($order, $creator);
            /**
             * Tính toán lại tồn tạm tính
             */
            foreach ($order->orderStocks as $orderStock) {
                dispatch(new UpdateTemporaryStockJob($orderStock->stock));
            }
        }

        /**
         * Đơn hoàn hàng tính lại phí vận chuyển dự kiến
         */
        if ($order->status == Order::STATUS_RETURN_COMPLETED) {
            (new OrderShippingFinancialStatusChanged($order, Order::SFS_RECONCILIATION, $order->shipping_financial_status, $creator))->queue();
            try {
                $order->orderPacking->shippingPartner->expectedTransporting()->getReturnPrice($order);
            } catch (ExpectedTransportingPriceException $exception) {

            }
        }

        /**
         * Đơn chuyển chờ giao hàng sẽ tính lại phí vận chuyển dự kiến và snapshot
         */
        if ($order->status == Order::STATUS_WAITING_DELIVERY) {
            try {
                $order->orderPacking->shippingPartner->expectedTransporting()->getPrice($order, true, true);
            } catch (ExpectedTransportingPriceException $exception) {

            }
        }

        /**
         * Đơn giao hàng thành công
         */
        if ($order->status == Order::STATUS_DELIVERED) {
            (new OrderShippingFinancialStatusChanged($order, Order::SFS_RECONCILIATION, $order->shipping_financial_status, $creator))->queue();
        }

        return $res;
    }

    /**
     * Hủy các YCDH ở trạng thái lỗi và chờ xử lý
     * @param Order $order
     * @param User $creator
     * @throws WorkflowException
     */
    public function cancelOrderPacking(Order $order, User $creator)
    {
        $orderPackings = $order->orderPackings()->whereIn('status', [
            OrderPacking::STATUS_WAITING_PROCESSING,
            OrderPacking::STATUS_WAITING_PICKING,
            OrderPacking::STATUS_WAITING_PACKING
        ])->get();
        /** @var OrderPacking $orderPacking */
        foreach ($orderPackings as $orderPacking) {
            if ($orderPacking->canChangeStatus(OrderPacking::STATUS_CANCELED)) {
                $orderPacking->changeStatus(OrderPacking::STATUS_CANCELED, $creator);
            }
        }
    }
}
