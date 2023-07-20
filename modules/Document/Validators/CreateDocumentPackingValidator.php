<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Illuminate\Database\Eloquent\Collection;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Tenant\Models\Tenant;
use Modules\Warehouse\Models\Warehouse;

class CreateDocumentPackingValidator extends Validator
{
    /**
     * @var Warehouse
     */
    protected $warehouse;

    /** @var Collection */
    protected $orderPackings;

    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * DocumentPackingScanValidator constructor.
     * @param Tenant $tenant
     * @param array $input
     */
    public function __construct(Tenant $tenant, array $input = [])
    {
        $this->tenant = $tenant;
        parent::__construct($input);
    }

    public function rules()
    {
        return [
            'warehouse_id' => 'required',
            'order_packings' => 'required|array',
            'order_packings.*.order_packing_id' => 'required',
            'order_packings.*.scanned_at' => 'required|date_format:Y-m-d H:i:s',
            'scan_type' => 'required|in:' . implode(",", [OrderPacking::SCAN_TYPE_ORDER, OrderPacking::SCAN_TYPE_FREIGHT_BILL]),
        ];
    }

    /**
     * @return Collection
     */
    public function getOrderPackings(): Collection
    {
        return $this->orderPackings;
    }

    protected function customValidate()
    {
        if (!$this->warehouse = $this->tenant->warehouses()->firstWhere(['id' => $this->input['warehouse_id']])) {
            $this->errors()->add('warehouse_id', static::ERROR_INVALID);
            return;
        }

        $orderPackingDatas    = $this->input('order_packings', []);
        $collectOrderPackings = collect($orderPackingDatas);
        $orderPackingIds      = $collectOrderPackings->pluck('order_packing_id')->all();
        $this->orderPackings        = OrderPacking::query()->where('tenant_id', $this->tenant->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->whereIn('id', $orderPackingIds)->get();
        if ($this->orderPackings->count() < count($orderPackingDatas)) {
            $this->errors()->add('order_packings', static::ERROR_INVALID);
            return;
        }

        foreach ($this->orderPackings as $orderPacking) {
            if (!in_array($orderPacking->status, [OrderPacking::STATUS_WAITING_PICKING, OrderPacking::STATUS_WAITING_PACKING])) {
                $errors[] = [
                    'order_packing_id' => $orderPacking->id,
                    'order_packing_status' => $orderPacking->status,
                    'order_id' => $orderPacking->order_id,
                    'order_code' => $orderPacking->order->code,
                    'freight_bill_code' => $orderPacking->freightBill ? $orderPacking->freightBill->freight_bill_code : null,
                ];
            }
        }

        if (!empty($errors)) {
            $this->errors()->add('exists_order_packing_not_process', $errors);
            return;
        }
    }

    /**
     * @return Warehouse
     */
    public function getWarehouse()
    {
        return $this->warehouse;
    }
}
