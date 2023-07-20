<?php

namespace Modules\OrderPacking\Services;

use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Models\Order;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\OrderPacking\Models\PickingSession;
use Modules\OrderPacking\Models\PickingSessionPiece;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;

interface OrderPackingServiceInterface
{
    /**
     * Tạo yêu cầu đóng hàng
     *
     * @param Order $order
     * @return OrderPacking|null
     */
    public function createOrderPacking(Order $order): ?OrderPacking;

    /**
     * Cập nhật hoặc tạo mới YCĐH
     *
     * @param Order $order
     * @return OrderPacking
     */
    public function updateOrderPackings(Order $order);

    /**
     * @param array $filter
     * @return LengthAwarePaginator|Builder|Builder[]|Collection
     */
    public function listing(array $filter);

    /**
     * @param array $filter
     * @return ModelQuery
     */
    public function query(array $filter);

    /**
     * Tạo vận đơn
     * @param OrderPacking $orderPacking
     * @param $creatorId
     * @param $pickupType
     * @return mixed
     */
    public function createTrackingNo(OrderPacking $orderPacking, $creatorId, $pickupType = null);

    /**
     * Mapping vận đơn với M32
     * @param OrderPacking $orderPacking
     * @param $creatorId
     * @return mixed
     */
    public function mappingTrackingNo(OrderPacking $orderPacking, $creatorId);

    /**
     * Tạo vận đơn thủ công
     * Áp dụng trong TH vận hành tự import mã vd hoặc tự tạo mã vđ với đơn seller dùng vận chuyển ngoài
     *
     * @param OrderPacking $orderPacking
     * @param string $freightBillCode
     * @param User $user
     * @param ShippingPartner|null $shippingPartner
     * @return FreightBill
     */
    public function createTrackingNoByManual(OrderPacking $orderPacking, string $freightBillCode, User $user, ShippingPartner $shippingPartner = null, string $freightBillStatus = null): FreightBill;

    /**
     * Huy vận đơn
     * @param OrderPacking $orderPacking
     * @param $creatorId
     * @return mixed
     */
    public function cancelTrackingNo(OrderPacking $orderPacking, $creatorId);

    /**
     * @param array $orderPackingIds
     * @param Warehouse $warehouse
     * @return string
     */
    public function donwloadListItemsByIds(array $orderPackingIds, Warehouse $warehouse);

    /**
     * @param array $filter
     * @param Warehouse $warehouse
     * @return mixed
     */
    public function downloadListItemsByFilter(array $filter, Warehouse $warehouse);

    /**
     * Download danh sách mẫu tạo vận đơn trên đơn vị vận chuyển theo danh sách YCĐH
     *
     * @param ShippingPartner $shippingPartner
     * @param array $orderPackingIds
     * @return mixed
     */
    public function donwloadTempTrackingsByIds(ShippingPartner $shippingPartner, array $orderPackingIds);

    /**
     * Download danh sách mẫu tạo vận đơn trên đơn vị vận chuyển theo danh sách YCĐH
     *
     * @param ShippingPartner $shippingPartner
     * @param array $filter
     * @return mixed
     */
    public function downloadTempTrackingsByFilter(ShippingPartner $shippingPartner, array $filter);

    /**
     * Tạo snapshots cho yêu cầu chuẩn bị hàng
     *
     * @param OrderPacking $orderPacking
     * @return array
     */
    public function makeSnapshots(OrderPacking $orderPacking);

    /**
     * @param OrderPacking $orderPacking
     * @return array
     */
    public function makeItemData(OrderPacking $orderPacking);

    /**
     * Update mã vận đơn cho yêu cầu đóng hàng
     *
     * @param OrderPacking $orderPacking
     * @param FreightBill $freightBill
     * @return OrderPacking
     */
    public function updateFreightBill(OrderPacking $orderPacking, FreightBill $freightBill);

    /**
     * Gán YCDH cho nhân viên nhặt hàng
     *
     * @param Collection $orderPackings
     * @param User $picker
     * @param User $creator
     * @return mixed
     */
    public function grantPicker(Collection $orderPackings, User $picker, User $creator);

    /**
     * Tạo phiên nhặt hàng
     *
     * @param WarehouseArea $warehouseArea
     * @param Collection $orderPackings
     * @param User $user
     * @return PickingSession
     */
    public function createPickingSession(WarehouseArea $warehouseArea, Collection $orderPackings, User $user);

    /**
     * Đánh dấu đã nhặt hàng ở 1 lượt nhặt hàng
     *
     * @param PickingSessionPiece $pickingSessionPiece
     * @param User $user
     * @return PickingSessionPiece
     */
    public function pickedPiece(PickingSessionPiece $pickingSessionPiece, User $user);

    /**
     * @param PickingSession $pickingSession
     * @param User $user
     * @return PickingSession
     */
    public function pickedPickingSession(PickingSession $pickingSession, User $user);

    /**
     * Cập nhật remark orderPacking
     *
     * @param OrderPacking $orderPacking
     * @return void
     */
    public function updateRemark(OrderPacking $orderPacking);

    /**
     * Thay đổi trạng thái ycdh thủ công,
     * không bắt buộc theo đúng workflow, sử dụng với tools internal
     *
     * @param OrderPacking $orderPacking
     * @param $status
     * @param User $user
     * @return void
     */
    public function changeStatusWithoutWorkflow(OrderPacking $orderPacking, $status, User $user);
}
