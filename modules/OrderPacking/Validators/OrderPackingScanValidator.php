<?php /** @noinspection SpellCheckingInspection */

namespace Modules\OrderPacking\Validators;

use App\Base\Validator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Document\Models\Document;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Models\Order;
use Modules\Tenant\Models\Tenant;
use Modules\Warehouse\Models\Warehouse;

class OrderPackingScanValidator extends Validator
{
    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var Warehouse
     */
    protected $warehouse;

    /**
     * @var FreightBill|null
     */
    protected $freightBill;

    /**
     * @var Order|null
     */
    protected $order;

    /**
     * @var OrderPacking|null
     */
    protected $orderPacking;

    /**
     * OrderPackingScanValidator constructor.
     * @param Tenant $tenant
     * @param array $input
     */
    public function __construct(Tenant $tenant, array $input = [])
    {
        $this->tenant = $tenant;
        parent::__construct($input);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => 'required',
            'barcode_type' => 'required|in:' . implode(",", [OrderPacking::SCAN_TYPE_ORDER, OrderPacking::SCAN_TYPE_FREIGHT_BILL]),
            'barcode' => 'required',
        ];
    }

    protected function customValidate()
    {
        if (!empty($this->input['warehouse'])) {
            $this->warehouse = $this->input['warehouse'];
        } else {
            $this->warehouse = $this->tenant->warehouses()->find($this->input['warehouse_id']);
        }

        if (!$this->warehouse instanceof Warehouse) {
            $this->errors()->add('warehouse_id', static::ERROR_EXISTS);
            return false;
        }

        $query = OrderPacking::query()->where('warehouse_id', $this->warehouse->id);

        $passed = $this->input['barcode_type'] == OrderPacking::SCAN_TYPE_ORDER
            ? $this->validateOrderCode($query)
            : $this->validateFreightBillCode($query);

        if (!$passed) {
            return false;
        }

        $orderPackings = $query->get();
        foreach ($orderPackings as $orderPacking) {
            $order           = $orderPacking->order;
            $countOrderStock = $order->orderStocks->count();
            $countOrderSku   = $order->orderSkus->count();
            // kiểm tra đơn xem đã chọn vị trí chưa nếu chưa sẽ thông báo lỗi
            if ($countOrderStock != $countOrderSku) {
                $this->errors()->add('order_inspected', static::ERROR_INVALID);
                return;
            }
        }
        $unprocessedOrderPackings = $orderPackings->whereIn('status', [OrderPacking::STATUS_WAITING_PICKING, OrderPacking::STATUS_WAITING_PACKING]);

        // Nếu chỉ có 1 YCĐH chưa xử lý
        if ($unprocessedOrderPackings->count() == 1) {
            $this->orderPacking = $unprocessedOrderPackings->first();
            return true;
        }

        // Nếu có nhiều YCĐH chưa xử lý
        if ($unprocessedOrderPackings->count() > 1) {
            $this->errors()->add('has_many_unprocessed_order_packings', []);
            return false;
        }

        // Nếu có YCĐH đã được xử lý
        $finishedOrderPacking = $orderPackings->firstWhere('status', OrderPacking::STATUS_PACKED);
        if ($finishedOrderPacking instanceof OrderPacking) {
            $this->errors()->add('has_finished_order_packing', [
                'document' => $finishedOrderPacking->documents()->firstWhere('status', Document::STATUS_COMPLETED),
            ]);
            return false;
        }

        // Các case khác coi như không tìm thấy YCĐH hợp lệ nào
        $this->errors()->add('not_found_order_packing', []);
        return false;
    }

    /**
     * @param Builder $orderPackingQuery
     * @return bool
     */
    protected function validateOrderCode($orderPackingQuery)
    {
        if (!$this->order = $this->tenant->orders()->firstWhere(['code' => $this->input['barcode']])) {
            $this->errors()->add('order_code', static::ERROR_EXISTS);
            return false;
        }

        if (!$this->order->inspected) {
            $this->errors()->add('order_inspected', static::ERROR_INVALID);
            return false;
        }

        // Nếu không có YCĐH ở kho đang quét nhưng có ở kho khác
        if ($this->hasOrderPackingInOtherWarehouses()) {
            $this->errors()->add('has_order_packing_in_other_warehouses', [
                'order' => $this->order,
                'warehouse' => $this->warehouse,
            ]);
            return false;
        }

        $orderPackingQuery->where('order_id', $this->order->id);

        return true;
    }

    /**
     * Return true nếu không có YCĐH ở kho đang quét nhưng có ở kho khác
     *
     * @return bool
     */
    protected function hasOrderPackingInOtherWarehouses()
    {
        $orderPackings = OrderPacking::query()
            ->where('order_id', $this->order->id)
            ->whereIn('status', [OrderPacking::STATUS_WAITING_PROCESSING, OrderPacking::STATUS_WAITING_PICKING, OrderPacking::STATUS_WAITING_PACKING])
            ->get();

        $countInCurrentWarehouse = $orderPackings->where('warehouse_id', $this->warehouse->id)->count();

        return !$countInCurrentWarehouse && $orderPackings->count();
    }

    /**
     * @param Builder $orderPackingQuery
     * @return bool
     */
    protected function validateFreightBillCode($orderPackingQuery)
    {
        $freightBills = $this->tenant->freightBills()->where('freight_bill_code', $this->input['barcode'])
            ->where('status', '<>', FreightBill::STATUS_CANCELLED)
            ->get();

        if (!$freightBills->count()) {
            $this->errors()->add('freight_bill_code', static::ERROR_EXISTS);
            return false;
        }


        $orderPackingProcess  = 0;
        $finishedOrderPacking = null;

        /** @var FreightBill $freightBill */
        foreach ($freightBills as $freightBill) {
            if (!$freightBill->shipping_partner_id) {
                continue;
            }
            if (in_array($freightBill->orderPacking->status, [OrderPacking::STATUS_WAITING_PROCESSING, OrderPacking::STATUS_WAITING_PICKING, OrderPacking::STATUS_WAITING_PACKING])) {
                $this->freightBill   = $freightBill;
                $orderPackingProcess += 1;
            } else if ($freightBill->orderPacking->status == OrderPacking::STATUS_PACKED) {
                $finishedOrderPacking = $freightBill->orderPacking;
            }
        }

        if ($orderPackingProcess > 1) {
            $this->errors()->add('freight_bill_code', static::ERROR_UNIQUE);
            return false;
        }


        if (!$this->freightBill instanceof FreightBill) {
            if ($finishedOrderPacking instanceof OrderPacking) {
                $this->errors()->add('has_finished_order_packing', [
                    'document' => $finishedOrderPacking->documents()->firstWhere('status', Document::STATUS_COMPLETED),
                ]);
                return false;
            }

            $this->errors()->add('freight_bill_code', static::ERROR_EXISTS);
            return false;
        }

        if ($this->freightBill && !$this->freightBill->order->inspected) {
            $this->errors()->add('order_inspected', static::ERROR_INVALID);
            return false;
        }

        $orderPackingQuery->where('freight_bill_id', $this->freightBill->id);

        return true;
    }

    /**
     * @return Warehouse
     */
    public function getWarehouse()
    {
        return $this->warehouse;
    }

    /**
     * @return FreightBill|null
     */
    public function getFreightBill()
    {
        return $this->freightBill;
    }

    /**
     * @return Order|null
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return OrderPacking|null
     */
    public function getOrderPacking()
    {
        return $this->orderPacking;
    }
}
