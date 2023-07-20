<?php

namespace Modules\OrderPacking\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Modules\OrderPacking\Models\PickingSession;
use Modules\OrderPacking\Models\PickingSessionPiece;
use Modules\OrderPacking\Validators\CreatingPickingSessionValidator;
use Modules\OrderPacking\Validators\GettingProcessingPickingSessionValidator;
use Modules\OrderPacking\Validators\PickedPieceValidator;
use Modules\Service;

class PickingSessionController extends Controller
{

    /**
     * Tìm phiên nhặt hàng còn chưa kết thúc
     *
     * @return JsonResponse
     */
    public function processingPickingSession()
    {
        $inputs    = $this->request()->only(['warehouse_id']);
        $validator = new GettingProcessingPickingSessionValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        return $this->response()->success(['picking_session' => $validator->getProcessingPickingSession()]);

    }

    /**
     * Tạo phiên nhặt hàng
     * Response trả về các lượt nhặt hàng
     *
     * @return JsonResponse
     */
    public function create()
    {
        $inputs = $this->requests->only([
            'order_number',
            'warehouse_area_id',
        ]);

        $validator = new CreatingPickingSessionValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $pickingSession = Service::orderPacking()->createPickingSession($validator->getWarehouseArea(), $validator->getOrderPackings(), $this->user);
        return $this->response()->success([
            'picking_session' => $pickingSession,
            'picking_session_pieces' => $pickingSession->pickingSessionPieces->load(['order', 'sku', 'warehouseArea'])->sortBy('ranking')->map(function (PickingSessionPiece $pickingSessionPiece) {
                return array_merge(
                    $pickingSessionPiece->attributesToArray(),
                    [
                        'order_code' => $pickingSessionPiece->order->code,
                        'sku_code' => $pickingSessionPiece->sku->code,
                        'sku_image' => $pickingSessionPiece->sku->images ? $pickingSessionPiece->sku->images[0] : null,
                        'warehouse_area_name' => $pickingSessionPiece->warehouseArea->name,
                        'warehouse_area_code' => $pickingSessionPiece->warehouseArea->code,
                    ]
                );
            })->values()
        ]);
    }

    /**
     * @param PickingSession $pickingSession
     * @return JsonResponse
     */
    public function detail(PickingSession $pickingSession)
    {
        return $this->response()->success([
            'picking_session' => $pickingSession,
            'picking_session_pieces' => $pickingSession->pickingSessionPieces->load(['order', 'sku', 'warehouseArea'])->sortBy('ranking')->map(function (PickingSessionPiece $pickingSessionPiece) {
                return array_merge(
                    $pickingSessionPiece->attributesToArray(),
                    [
                        'order_code' => $pickingSessionPiece->order->code,
                        'sku_code' => $pickingSessionPiece->sku->code,
                        'sku_image' => $pickingSessionPiece->sku->product->images ? $pickingSessionPiece->sku->product->images[0] : null,
                        'warehouse_area_name' => $pickingSessionPiece->warehouseArea->name,
                        'warehouse_area_code' => $pickingSessionPiece->warehouseArea->code,
                    ]
                );
            })->values()
        ]);
    }

    /**
     * @param PickingSession $pickingSession
     * @param PickingSessionPiece $pickingSessionPiece
     * @return JsonResponse
     */
    public function pickedPiece(PickingSession $pickingSession, PickingSessionPiece $pickingSessionPiece)
    {
        $validator = new PickedPieceValidator($pickingSessionPiece);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        if ($pickingSessionPiece->is_picked) {
            return $this->response()->error('INPUT_INVALID', ['code' => 'is_picked']);
        }
        $pickingSessionPiece = Service::orderPacking()->pickedPiece($pickingSessionPiece, $this->user);

        return $this->response()->success(['picking_session_piece' => $pickingSessionPiece]);
    }

    /**
     * @param PickingSession $pickingSession
     * @return JsonResponse
     */
    public function pickedPickingSession(PickingSession $pickingSession)
    {
        if ($pickingSession->is_picked) {
            return $this->response()->error('INPUT_INVALID', ['code' => 'is_picked']);
        }
        $pickingSession = Service::orderPacking()->pickedPickingSession($pickingSession, $this->user);

        return $this->response()->success([
            'picking_session' => $pickingSession,
            'picking_session_pieces' => $pickingSession->pickingSessionPieces->load(['order', 'sku', 'warehouseArea'])->sortBy('ranking')->map(function (PickingSessionPiece $pickingSessionPiece) {
                return array_merge(
                    $pickingSessionPiece->attributesToArray(),
                    [
                        'order_code' => $pickingSessionPiece->order->code,
                        'sku_code' => $pickingSessionPiece->sku->code,
                        'sku_image' => $pickingSessionPiece->sku->images ? $pickingSessionPiece->sku->images[0] : null,
                        'warehouse_area_name' => $pickingSessionPiece->warehouseArea->name,
                        'warehouse_area_code' => $pickingSessionPiece->warehouseArea->code,
                    ]
                );
            })
        ]);
    }
}
