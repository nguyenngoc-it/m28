<?php

namespace Modules\OrderExporting\Validators;

use App\Base\Validator;
use Modules\Document\Models\Document;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Models\Order;
use Modules\OrderExporting\Models\OrderExporting;
use Modules\Warehouse\Models\Warehouse;

class OrderExportingScanValidator extends Validator
{
    /** @var OrderExporting $orderExporting */
    protected $orderExporting;

    public function rules()
    {
        return [
            'warehouse_id' => 'required|int',
            'barcode_type' => 'required|in:' . Document::DOCUMENT_BARCODE_TYPE_ORDER . ',' . Document::DOCUMENT_BARCODE_TYPE_FREIGHT_BILL,
            'barcode' => 'required',
        ];
    }

    /**
     * @return OrderExporting
     */
    public function getOrderExporting(): OrderExporting
    {
        return $this->orderExporting;
    }

    protected function customValidate()
    {
        $barcode = $this->input('barcode');
        $type    = $this->input('barcode_type');
        if (!$warehouse = Warehouse::find($this->input('warehouse_id', 0))) {
            $this->errors()->add('warehouse_id', static::ERROR_EXISTS);
            return;
        }
        if ($type == Document::DOCUMENT_BARCODE_TYPE_FREIGHT_BILL) {
            $freightBillQuery = FreightBill::query()->where('freight_bill_code', $barcode);
        } else {
            /** @var Order|null $order */
            $order = Order::query()->where([
                'tenant_id' => $this->user->tenant_id,
                'code' => $barcode
            ])->first();
            if (empty($order)) {
                $this->errors()->add('order', static::ERROR_EXISTS);
                return;
            }
            $freightBillQuery = FreightBill::query()->where('order_id', $order->id);
        }

        $freightBills = $freightBillQuery->where('status','<>',FreightBill::STATUS_CANCELLED)->get()->filter(function (FreightBill $freightBill) use ($warehouse) {
            return $freightBill->currentOrderPacking && $freightBill->currentOrderPacking->warehouse_id == $warehouse->id && $freightBill->currentOrderPacking->orderExporting;
        })->values();

        /**
         * TH quét ra 2 YCXH báo lỗi để kiểm tra lại
         */
        if ($freightBills->count() > 1) {
            $this->errors()->add('barcode', [
                'code' => static::ERROR_INVALID,
                'orders' => $freightBills->map(function (FreightBill $freightBill) {
                    return $freightBill->order->only(['id', 'code']);
                })
            ]);
            return;
        }

        /** @var FreightBill|null $freightBill */
        $freightBill          = $freightBills->count() == 1 ? $freightBills->first() : null;
        $this->orderExporting = $freightBill ? $freightBill->orderPacking->orderExporting : null;

        /**
         * Không tìm thấy YCXH tương ứng
         */
        if (!$this->orderExporting) {
            if ($type == Document::DOCUMENT_BARCODE_TYPE_ORDER) {
                $this->errors()->add('order', 'order_exporting_not_found');
                return;
            }
            $this->errors()->add('barcode', static::ERROR_EXISTS);
            return;
        }

        /**
         * YCXH nằm ở kho khác
         */
        if ($this->orderExporting->warehouse_id != $warehouse->id) {
            if ($type == Document::DOCUMENT_BARCODE_TYPE_FREIGHT_BILL) {
                $this->errors()->add('barcode', static::ERROR_EXISTS);
                return;
            }
            if ($type == Document::DOCUMENT_BARCODE_TYPE_ORDER) {
                $this->errors()->add('order', 'warehouse_invalid');
                return;
            }
        }

        /**
         * YCXH đã/đang xử lý ở 1 chứng từ xuất hàng khác
         */
        if ($this->orderExporting->status != OrderExporting::STATUS_NEW) {
            $this->errors()->add('barcode', [
                'code' => static::ERROR_INVALID,
                'document_exporting' => $this->orderExporting->documentExporting()->only(['id', 'code', 'created_at', 'verified_at'])
            ]);
        }
    }
}
