<?php

namespace Modules\OrderPacking\Validators;

use App\Base\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Warehouse\Models\WarehouseArea;

class CreatingPickingSessionValidator extends Validator
{

    /** @var Collection */
    protected $orderPackings;
    /** @var WarehouseArea */
    protected $warehouseArea;

    public function rules()
    {
        return [
            'order_number' => 'required',
            'warehouse_area_id' => 'required|int',
        ];
    }

    /**
     * @return Collection
     */
    public function getOrderPackings(): Collection
    {
        return $this->orderPackings;
    }

    /**
     * @return WarehouseArea
     */
    public function getWarehouseArea(): WarehouseArea
    {
        return $this->warehouseArea;
    }

    protected function customValidate()
    {
        $warehouseAreaId = $this->input('warehouse_area_id');
        $orderNumber     = (int)$this->input('order_number');

        $this->warehouseArea = WarehouseArea::find($warehouseAreaId);
        if (!$this->warehouseArea) {
            $this->errors()->add('warehouse_area_id', static::ERROR_EXISTS);
            return;
        }
        $warehouse = $this->warehouseArea->warehouse;

        /**
         * Nếu còn phiên nhặt chưa hoàn tất thì ko cho tạo thêm
         */
        $processingPickingSession = $this->user->pickingSessions->where('is_picked', false)->first();
        if ($processingPickingSession) {
            $this->errors()->add('processing_picking_session', ['picking_session' => $processingPickingSession]);
            return;
        }

        $this->orderPackings = OrderPacking::query()->where([
            'warehouse_id' => $warehouse->id,
            'picker_id' => $this->user->id,
            'status' => OrderPacking::STATUS_WAITING_PICKING
        ])->where(function (Builder $builder) {
            $builder->whereNull('picking_session_id')->orWhere('picking_session_id', 0);
        })->orderBy('grant_picker_at')->get();
        if ($this->orderPackings->count() < $orderNumber) {
            $this->errors()->add('order_number', static::ERROR_INVALID);
            return;
        }
        $this->orderPackings = $this->orderPackings->take($orderNumber)->values();
    }
}
