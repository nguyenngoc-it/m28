<?php

namespace Modules\Order\Listeners;

use App\Base\QueueableListener;
use Gobiz\Log\LogService;
use Gobiz\Workflow\WorkflowException;
use Modules\FreightBill\Models\FreightBill;
use Modules\Location\Models\Location;
use Modules\Location\Models\LocationShippingPartner;
use Modules\Marketplace\Services\Marketplace;
use Modules\Order\Events\OrderCreated;
use Modules\Order\Jobs\UpdateLocationShippingPartnerJob;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderSku;
use Modules\Order\Services\OrderEvent;
use Modules\OrderIntegration\PublicEvents\OrderUpdated;
use Modules\Service;
use Modules\Tenant\Models\TenantSetting;
use Modules\WarehouseStock\Jobs\CalculateWarehouseStockJob;

class CreatedOrderListener extends QueueableListener
{
    /**
     * @param OrderCreated $event
     * @throws WorkflowException
     */
    public function handle(OrderCreated $event)
    {
        $order        = $event->order;
        $targetStatus = $event->targetStatus;
        /**
         * Lưu log tạo đơn
         */
        $order->logActivity(OrderEvent::CREATE, $event->order->creator, [], [
            'time' => $event->order->created_at,
        ]);

        /**
         * Tự động chọn kho xuất
         */
        if (in_array($order->status, [Order::STATUS_WAITING_INSPECTION, Order::STATUS_WAITING_CONFIRM, Order::STATUS_WAITING_PROCESSING])) {
            Service::order()->autoInspection($order, $order->creator);
        }

        /**
         * Nếu có kho xuất chuyển "chờ xử lý"
         */
        if ($order->warehouse_id && $order->canChangeStatus(Order::STATUS_WAITING_PROCESSING)) {
            $order->changeStatus(Order::STATUS_WAITING_PROCESSING, $order->creator);
        }

        /**
         * Cập nhật thống kê stocks theo kho
         */
        if (!empty($order->warehouse_id)) {
            foreach ($order->orderSkus as $orderSku) {
                dispatch(new CalculateWarehouseStockJob($orderSku->sku_id, $order->warehouse_id));
            }
        }

        /**
         * Khởi tạo bản ghi mã vận đơn nếu đơn nhập vận đơn ngoài
         */
        if ($order->freight_bill) {
            $existFreightBill = FreightBill::query()->where([
                'order_id' => $order->id,
                'freight_bill_code' => $order->freight_bill
            ])->first();
            if (!$existFreightBill) {
                FreightBill::updateOrCreate(
                    [
                        'freight_bill_code' => $order->freight_bill,
                        'shipping_partner_id' => $order->shipping_partner_id,
                        'tenant_id' => $order->tenant_id,
                        'order_id' => $order->id,
                    ],
                    [
                        'snapshots' => $order->orderPacking ? Service::orderPacking()->makeSnapshots($order->orderPacking) : null,
                        'status' => FreightBill::STATUS_WAIT_FOR_PICK_UP,
                        'order_packing_id' => $order->orderPacking ? $order->orderPacking->id : 0,
                    ]
                );
            }
        }

        /**
         * Tính phí dịch vụ
         *
         */
        $order->extent_service_expected_amount = $order->extentServiceAmountInit();
        $order->save();

        /**
         * TH đồng bộ đơn từ các kênh bán mà tạo đơn ko ở trạng thái WAITING_INSPECTION thì chuyển tiếp sang trạng thái tương ứng
         */
        if ($order->canChangeStatus($targetStatus)) {
            $order->changeStatus($targetStatus, $order->creator, ['reason' => 'target-status']);
        }


        /**
         * Trường hợp đơn tạo ra mà DVVC chưa được map vào với 1 thị trường nào đó thì tự động map theo thị trường của merchant
         */
        if (
            $order->shipping_partner_id && $order->merchant &&
            !LocationShippingPartner::query()->where('shipping_partner_id', $order->shipping_partner_id)->count()
        ) {
            dispatch(new UpdateLocationShippingPartnerJob($order->id));
        }

        $this->publishOrderCreatedToKafka($order);
    }

    /**
     * @param Order $order
     * @return void
     */
    protected function publishOrderCreatedToKafka(Order $order)
    {
        if(!(int)$order->tenant->getSetting(TenantSetting::PUBLISH_EVENT_ORDER_CREATE)) {
            return false;
        }

        $order = $order->refresh();

        try {
            $orderSkus = $order->orderSkus->load('sku');
            $data = [
                'skus' => $orderSkus->map(function (OrderSku $orderSku) {
                    return array_merge($orderSku->attributesToArray(), [
                        'sku_code' => $orderSku->sku->code
                    ]);
                }),
                'store' => $order->store ? $order->store->only(['id', 'name', 'marketplace_code', 'marketplace_store_id']) : null,
                'country' => $order->receiverCountry ? $order->receiverCountry->only(['id', 'code', 'label']) : null,
                'province' => $order->receiverProvince ? $order->receiverProvince->only(['id', 'code', 'label']) : null,
                'district' => $order->receiverDistrict ? $order->receiverDistrict->only(['id', 'code', 'label']) : null,
                'ward' => $order->receiverWard ? $order->receiverWard->only(['id', 'code', 'label']) : null,
                'merchant' => $order->merchant->only(['id', 'code', 'name']),
                'shipping_partner' => $order->shippingPartner ? $order->shippingPartner->only(['id', 'name', 'code']) : null,
                'order_transactions' => $order->orderTransactions->toArray()
            ];
            (new OrderUpdated($order, OrderEvent::CREATE, $data))->publish();
        } catch (\Exception $exception) {
            LogService::logger('create_order')->info('order_created event error '.$exception->getMessage() . ' '.$order->code);
        }
    }
}
