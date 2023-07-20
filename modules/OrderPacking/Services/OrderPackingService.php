<?php

namespace Modules\OrderPacking\Services;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Carbon\Carbon;
use Gobiz\ModelQuery\ModelQuery;
use Gobiz\Workflow\WorkflowException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderSku;
use Modules\Order\Models\OrderStock;
use Modules\Order\Services\OrderEvent;
use Modules\OrderPacking\Commands\CancelTrackingNo;
use Modules\OrderPacking\Commands\CreateOrderPacking;
use Modules\OrderPacking\Commands\CreateTrackingNo;
use Modules\OrderPacking\Commands\CreatingPickingSession;
use Modules\OrderPacking\Commands\DownloadListItemsByFilter;
use Modules\OrderPacking\Commands\DownloadListItemsByIds;
use Modules\OrderPacking\Commands\DownloadTempTrackingByFilter;
use Modules\OrderPacking\Commands\DownloadTempTrackingByIds;
use Modules\OrderPacking\Commands\MappingTrackingNo;
use Modules\OrderPacking\Commands\PickedPickingSession;
use Modules\OrderPacking\Jobs\MappingTrackingNoJob;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\OrderPacking\Models\PickingSession;
use Modules\OrderPacking\Models\PickingSessionPiece;
use Modules\Service;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Stock\Models\Stock;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;

class OrderPackingService implements OrderPackingServiceInterface
{

    /**
     * Tạo yêu cầu đóng hàng
     *
     * @param Order $order
     * @return OrderPacking|null
     */
    public function createOrderPacking(Order $order): ?OrderPacking
    {
        return (new CreateOrderPacking($order))->handle();
    }

    /**
     * Cập nhật hoặc tạo mới YCĐH
     *
     * @param Order $order
     * @return OrderPacking
     */
    public function updateOrderPackings(Order $order)
    {
        /**
         * Tạo orderPacking
         */
        $orderWarehouseStock = $order->getWarehouseStock();
        $currentOrderPacking = $order->orderPacking;
        /** @var OrderPacking $orderPacking */
        $orderPacking = OrderPacking::updateOrCreate(
            [
                'tenant_id' => $order->tenant_id,
                'merchant_id' => $order->merchant_id,
                'order_id' => $order->id,
            ],
            [
                'total_quantity' => $order->orderSkus->sum('quantity'),
                'total_values' => $order->orderSkus->sum('order_amount'),
                'warehouse_id' => $orderWarehouseStock ? $orderWarehouseStock->id : 0,
                'shipping_partner_id' => $currentOrderPacking->shipping_partner_id ?: $order->shipping_partner_id,
                'receiver_name' => $order->receiver_name,
                'receiver_phone' => $order->receiver_phone,
                'receiver_address' => $order->receiver_address,
                'payment_type' => $order->payment_type,
                'payment_method' => $order->payment_method,
                'intended_delivery_at' => $order->intended_delivery_at,
            ]
        );
        /**
         * Cập nhật snapshot của vận đơn của YCĐH
         */
        $freightBill = $orderPacking->freightBill;
        if ($freightBill) {
            $snapshots                        = Service::orderPacking()->makeSnapshots($orderPacking->refresh());
            $freightBill->snapshots           = $snapshots;
            $freightBill->shipping_partner_id = $orderPacking->shipping_partner_id;
            $freightBill->save();
        }
        /**
         * Cập nhật remark
         */
        $this->updateRemark($orderPacking);
        /**
         * Nếu tồn tại nhiều hơn 1 orderPackings thì xoá 1 cái đi
         */
        if ($orderWarehouseStock && $order->orderPackings()->count() > 1) {
            $order->orderPackings()->where('warehouse_id', '<>', $orderWarehouseStock->id)->delete();
        }

        /**
         * Nếu đã có orderStocks thì update lại orderPackingItems
         */
        if ($order->orderStocks->count()) {
            Service::order()->updateOrderPackingItemsByOrder($order);
        }

        if ($orderPacking->orderExporting) {
            /**
             * cập nhật orderExporting
             */
            Service::orderExporting()->updateByOrderPacking($orderPacking->orderExporting);
            /**
             * Cập nhật orderExportingItems
             */
            Service::orderExporting()->updateOrderExportingItems($orderPacking->orderExporting, $orderPacking);
        }

        return $orderPacking;
    }

    /**
     * @param array $filter
     * @return LengthAwarePaginator|Builder|Builder[]|Collection
     */
    public function listing(array $filter)
    {
        $sortBy     = Arr::pull($filter, 'sort_by', 'id');
        $sort       = Arr::pull($filter, 'sort', 'desc');
        $page       = Arr::pull($filter, 'page', config('paginate.page'));
        $perPage    = Arr::pull($filter, 'per_page', config('paginate.per_page'));
        $paginate   = Arr::pull($filter, 'paginate', true);
        $exportData = Arr::pull($filter, 'exportData', false);

        $query = Service::orderPacking()->query($filter)->getQuery();
        $query->with(['order', 'merchant', 'shippingPartner']);
        $query->orderBy('order_packings' . '.' . $sortBy, $sort);

        if (!$paginate) {
            return $query->get();
        }

        if ($exportData) {
            return $query;
        }

        return $query->paginate($perPage, ['order_packings.*'], 'page', $page);
    }

    /**
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter)
    {
        return (new OrderPackingQuery())->query($filter);
    }

    /**
     * Tạo vận đơn
     * @param OrderPacking $orderPacking
     * @param $creatorId
     * @param string $pickupType
     * @return mixed|OrderPacking|null
     * @throws WorkflowException
     */
    public function createTrackingNo(OrderPacking $orderPacking, $creatorId, $pickupType = null)
    {
        return (new CreateTrackingNo($orderPacking, $creatorId, $pickupType))->handle();
    }

    /**
     * Mapping vận đơn với M32
     * @param OrderPacking $orderPacking
     * @param $creatorId
     * @return mixed
     * @throws WorkflowException
     */
    public function mappingTrackingNo(OrderPacking $orderPacking, $creatorId)
    {
        return (new MappingTrackingNo($orderPacking, $creatorId))->handle();
    }

    /**
     * Tạo vận đơn thủ công
     * Áp dụng trong TH vận hành tự import mã vd hoặc tự tạo mã vđ với đơn seller dùng vận chuyển ngoài
     *
     * @param OrderPacking $orderPacking
     * @param string $freightBillCode
     * @param User $user
     * @param ShippingPartner|null $shippingPartner
     * @param string|null $freightBillStatus
     * @return FreightBill
     * @throws WorkflowException
     */
    public function createTrackingNoByManual(OrderPacking $orderPacking, string $freightBillCode, User $user, ShippingPartner $shippingPartner = null, string $freightBillStatus = null): FreightBill
    {
        $orderPacking      = $orderPacking->refresh();
        $shippingPartnerId = $shippingPartner ? $shippingPartner->id : 0;

        /**
         * Cập nhật mã vđ cho YCDH và đơn
         * Nếu không có mã dvvc trên file thì update mã vđ theo dvvc trên YCĐH
         */
        $freightBill = FreightBill::updateOrCreate(
            [
                'freight_bill_code' => $freightBillCode,
                'tenant_id' => $orderPacking->tenant_id,
                'order_id' => $orderPacking->order->id,
            ],
            [
                'order_packing_id' => $orderPacking->id,
                'shipping_partner_id' => $shippingPartnerId,
                'snapshots' => Service::orderPacking()->makeSnapshots($orderPacking),
                'status' => $freightBillStatus ?: FreightBill::STATUS_WAIT_FOR_PICK_UP
            ]
        );
        /**
         * Huỷ những mã vận đơn trước đó
         */
        $cancelFreightBills = $orderPacking->order->freightBills()->where(function (Builder $builder) use ($freightBillCode, $shippingPartnerId) {
            $builder->where('freight_bill_code', '<>', $freightBillCode);
            $builder->orWhere(function (Builder $query) use ($freightBillCode, $shippingPartnerId) {
                $query->where('freight_bill_code', $freightBillCode)
                    ->where('shipping_partner_id', '<>', $shippingPartnerId);
            });
        })->get();
        /** @var FreightBill $cancelFreightBill */
        foreach ($cancelFreightBills as $cancelFreightBill) {
            Service::freightBill()->changeStatus($cancelFreightBill, FreightBill::STATUS_CANCELLED, $user);
        }

        $orderPacking->refresh();
        /**
         * Cập nhật đơn vị vc nếu YCĐH chưa có
         */
        $orderPacking->shipping_partner_id = $shippingPartnerId;
        if ($orderPacking->order->shipping_partner_id != $shippingPartnerId) {
            $orderPacking->order->shipping_partner_id = $shippingPartnerId;
            $orderPacking->order->save();
        }
        /**
         * Chuyển thông tin đơn sang M32 để kiểm tra lại xem vận đơn đã có trên M32 hay chưa
         * Nếu có rồi thì ko cần tạo mới, chỉ cần đồng bộ vận đơn với M32
         */
        if ($freightBill->shippingPartner && $freightBill->shippingPartner->provider = ShippingPartner::PROVIDER_M32) {
            dispatch(new MappingTrackingNoJob($orderPacking->id, $user->id));
        }

        /**
         * Cập nhập mã vận đơn hiện tại cho YCĐH
         */
        $orderPacking->freight_bill_id = $freightBill->id;
        $orderPacking->error_type      = null;
        $orderPacking->save();

        if ($orderPacking->canChangeStatus(OrderPacking::STATUS_WAITING_PICKING)) {
            $orderPacking->changeStatus(OrderPacking::STATUS_WAITING_PICKING, $user);
        }
        $orderPacking->logActivity(OrderPackingEvent::CREATE_FREIGHT_BILL, $user, $orderPacking->getChanges());

        return $freightBill;
    }


    /**
     * Huy vận đơn
     * @param OrderPacking $orderPacking
     * @param $creatorId
     * @return mixed
     */
    public function cancelTrackingNo(OrderPacking $orderPacking, $creatorId)
    {
        return (new CancelTrackingNo($orderPacking, $creatorId))->handle();
    }

    /**
     * @param array $orderPackingIds
     * @param Warehouse $warehouse
     * @return mixed
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function donwloadListItemsByIds(array $orderPackingIds, Warehouse $warehouse)
    {
        return (new DownloadListItemsByIds($orderPackingIds, $warehouse))->handle();
    }

    /**
     * Download danh sách mẫu tạo vận đơn trên đơn vị vận chuyển theo danh sách YCĐH
     *
     * @param ShippingPartner $shippingPartner
     * @param array $orderPackingIds
     * @return mixed
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function donwloadTempTrackingsByIds(ShippingPartner $shippingPartner, array $orderPackingIds)
    {
        return (new DownloadTempTrackingByIds($shippingPartner, $orderPackingIds))->handle();
    }

    /**
     * Download danh sách mẫu tạo vận đơn trên đơn vị vận chuyển theo danh sách YCĐH
     *
     * @param ShippingPartner $shippingPartner
     * @param array $filter
     * @return mixed
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function downloadTempTrackingsByFilter(ShippingPartner $shippingPartner, array $filter)
    {
        return (new DownloadTempTrackingByFilter($shippingPartner, $filter))->handle();
    }

    /**
     * @param array $filter
     * @param Warehouse $warehouse
     * @return mixed
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function downloadListItemsByFilter(array $filter, Warehouse $warehouse)
    {
        return (new DownloadListItemsByFilter($filter, $warehouse))->handle();
    }

    /**
     * Tạo snapshots cho yêu cầu chuẩn bị hàng
     *
     * @param OrderPacking $orderPacking
     * @return array
     */
    public function makeSnapshots(OrderPacking $orderPacking)
    {
        return ['items' => $this->makeItemData($orderPacking)];
    }

    /**
     * @param OrderPacking $orderPacking
     * @return array
     */
    public function makeItemData(OrderPacking $orderPacking)
    {
        $orderSkus = $orderPacking->order->orderSkus;
        $items     = [];
        /** @var OrderSku $orderSku */
        foreach ($orderSkus as $orderSku) {
            $sku     = $orderSku->sku;
            $items[] = [
                'id' => $sku->id,
                'code' => $sku->code,
                'name' => $sku->name,
                'price' => $orderSku->price,
                'quantity' => $orderSku->quantity
            ];
        }

        return $items;
    }

    /**
     * Update mã vận đơn cho yêu cầu đóng hàng
     *
     * @param OrderPacking $orderPacking
     * @param FreightBill $freightBill
     * @return OrderPacking
     * @throws WorkflowException
     */
    public function updateFreightBill(OrderPacking $orderPacking, FreightBill $freightBill)
    {
        $orderPacking->freight_bill_id     = $freightBill->id;
        $orderPacking->shipping_partner_id = $freightBill->shipping_partner_id;

        if (
            $orderPacking->status === OrderPacking::STATUS_WAITING_PROCESSING
        ) {
            if ($orderPacking->canChangeStatus(OrderPacking::STATUS_WAITING_PICKING)) {
                $orderPacking->changeStatus(OrderPacking::STATUS_WAITING_PICKING, Service::user()->getSystemUserDefault());
            }
        }

        return $orderPacking;
    }

    /**
     * Gán YCDH cho nhân viên nhặt hàng
     *
     * @param Collection $orderPackings
     * @param User $picker
     * @param User $creator
     * @return Collection
     */
    public function grantPicker(Collection $orderPackings, User $picker, User $creator)
    {
        $i = 0;
        /** @var OrderPacking $orderPacking */
        foreach ($orderPackings as $orderPacking) {
            if ($orderPacking->status == OrderPacking::STATUS_WAITING_PICKING && $orderPacking->order->inspected) {
                $orderPacking->picker_id       = $picker->id;
                $orderPacking->grant_picker_at = Carbon::now()->addSeconds($i);
                $orderPacking->save();
                $orderPacking->logActivity(OrderPackingEvent::GRANT_PICKER, $creator, ['picker' => $picker->only(['name', 'username'])]);
                $i++;
            }
        }

        return $orderPackings;
    }

    /**
     * Tạo phiên nhặt hàng
     *
     * @param WarehouseArea $warehouseArea
     * @param Collection $orderPackings
     * @param User $user
     * @return PickingSession
     */
    public function createPickingSession(WarehouseArea $warehouseArea, Collection $orderPackings, User $user)
    {
        return (new CreatingPickingSession($warehouseArea, $orderPackings, $user))->handle();
    }

    /**
     * Đánh dấu đã nhặt hàng ở 1 lượt nhặt hàng
     *
     * @param PickingSessionPiece $pickingSessionPiece
     * @param User $user
     * @return PickingSessionPiece
     */
    public function pickedPiece(PickingSessionPiece $pickingSessionPiece, User $user)
    {
        return DB::transaction(function () use ($pickingSessionPiece, $user) {
            $pickingSessionPiece->is_picked = true;
            $pickingSessionPiece->save();
            $pickingSessionPiece->pickingSession->order_packed_quantity++;
            $pickingSessionPiece->pickingSession->save();

            /**
             * chuyển sku từ vị trí nhặt sang thiét bị nhặt
             */
            /** @var Stock $stockExport */
            $stockExport = Stock::query()->where([
                'warehouse_area_id' => $pickingSessionPiece->warehouse_area_id,
                'sku_id' => $pickingSessionPiece->sku_id
            ])->first();
            $stockExport->export($pickingSessionPiece->quantity, $user, $pickingSessionPiece, Stock::ACTION_EXPORT_FOR_PICKING)->run();
            /** @var Stock $stockImport */
            $stockImport = Stock::query()->where([
                'warehouse_area_id' => $pickingSessionPiece->pickingSession->warehouse_area_id,
                'sku_id' => $pickingSessionPiece->sku_id
            ])->first();
            if (!$stockImport) {
                $stockImport = new Stock(
                    [
                        'tenant_id' => $pickingSessionPiece->tenant_id,
                        'product_id' => $pickingSessionPiece->sku->product->id,
                        'sku_id' => $pickingSessionPiece->sku_id,
                        'warehouse_id' => $pickingSessionPiece->warehouse_id,
                        'warehouse_area_id' => $pickingSessionPiece->pickingSession->warehouse_area_id,
                        'quantity' => 0,
                        'real_quantity' => 0,
                    ]
                );
                $stockImport->save();
            }
            $stockImport->import($pickingSessionPiece->quantity, $user, $pickingSessionPiece, Stock::ACTION_IMPORT_FOR_PICKING)->run();

            /**
             * Cập nhật orderStock của đơn
             */
            /** @var OrderStock $orderStock */
            $orderStock = $pickingSessionPiece->order->orderStocks->where('sku_id', $pickingSessionPiece->sku_id)->first();
            if ($orderStock) {
                $orderStock->stock_id          = $stockImport->id;
                $orderStock->warehouse_area_id = $pickingSessionPiece->pickingSession->warehouse_area_id;
                $orderStock->save();
                Service::order()->updateOrderPackingItems($orderStock);
            }

            $pickingSessionPiece->logActivity(PickingSessionEvent::PICKED_PIECE, $user, ['picking_session_id' => $pickingSessionPiece->pickingSession->id, 'piece_id' => $pickingSessionPiece->id]);
            return $pickingSessionPiece;
        });
    }

    /**
     * @param PickingSession $pickingSession
     * @param User $user
     * @return PickingSession
     */
    public function pickedPickingSession(PickingSession $pickingSession, User $user)
    {
        return (new PickedPickingSession($pickingSession, $user))->handle();
    }

    /**
     * Cập nhật remark orderPacking
     *
     * @param OrderPacking $orderPacking
     * @return void
     */
    public function updateRemark(OrderPacking $orderPacking)
    {
        $items         = Service::orderPacking()->makeItemData($orderPacking);
        $remark        = '';
        $totalQuantity = 0;
        foreach ($items as $item) {
            $remark        .= $item['name'] . ' x ' . $item['quantity'] . ' / ';
            $totalQuantity += $item['quantity'];
        }
        if (!empty($remark)) {
            $remark               = substr($remark, 0, -2);
            $remark               .= '- ' . $totalQuantity . 'PCS - ' . round($orderPacking->order->cod, 2);
            $remark               = mb_strimwidth($remark, 0, 250, '...');
            $orderPacking->remark = $remark;
            $orderPacking->save();
        }
    }

    /**
     * Thay đổi trạng thái ycdh thủ công,
     * không bắt buộc theo đúng workflow, sử dụng với tools internal
     *
     * @param OrderPacking $orderPacking
     * @param $status
     * @param User $user
     * @return void
     */
    public function changeStatusWithoutWorkflow(OrderPacking $orderPacking, $status, User $user)
    {
        if (!in_array($status, OrderPacking::$listStatus) || $orderPacking->status == $status) {
            return;
        }
        $orderPacking->logActivity(OrderEvent::CHANGE_STATUS, $user, [
            'order_packing' => $orderPacking,
            'old_status' => $orderPacking->status,
            'new_status' => $status,
        ]);
        $orderPacking->status = $status;
        $orderPacking->save();
    }
}
