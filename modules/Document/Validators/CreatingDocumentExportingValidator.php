<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use http\Env\Response;
use Modules\Document\Models\Document;
use Modules\Order\Models\Order;
use Modules\OrderExporting\Models\OrderExporting;
use Modules\Warehouse\Models\Warehouse;
use phpDocumentor\Reflection\Types\Static_;

class CreatingDocumentExportingValidator extends Validator
{
    /** @var Document */
    protected $documentPacking;
    /** @var Warehouse */
    protected $warehouse;

    public function rules()
    {
            $dataReturn = [
                'tenant_id' => 'required|int',
                'warehouse_id' => 'required',
                'barcode_type' => 'required|in:' . Document::DOCUMENT_BARCODE_TYPE_FREIGHT_BILL . ',' . Document::DOCUMENT_BARCODE_TYPE_ORDER,
                'order_exporting_ids' => 'required|array',
                'document_packing' => 'string',
                'receiver_name' => 'string',
                'receiver_phone' => 'string',
                'receiver_license' => 'string',
                'partner' => 'string',
            ];
        return $dataReturn;

    }

    /**
     * @return Document
     */
    public function getDocumentPacking(): Document
    {
        return $this->documentPacking;
    }

    /**
     * @return Warehouse
     */
    public function getWarehouse(): Warehouse
    {
        return $this->warehouse;
    }

    protected function customValidate()
    {
        $scan = data_get($this->input, 'scan');
        if (!$scan){
           $warehouseId = $this->input('warehouse_id', 0);
           if (!$this->warehouse = Warehouse::find($warehouseId)) {
               $this->errors()->add('warehouse_id', static::ERROR_EXISTS);
               return;
           }

           if ($documentPackingCode = $this->input('document_packing')) {
               $tenantId = $this->input('tenant_id', 0);
               if (empty(Document::query()->where([
                   'tenant_id' => $tenantId,
                   'code' => $documentPackingCode
               ])->first())) {
                   $this->errors()->add('document_packing', static::ERROR_EXISTS);
                   return;
               }
           }

           $orderExportingIds        = $this->input('order_exporting_ids');
           foreach ($orderExportingIds as $orderExportingId){
               $order = OrderExporting::find($orderExportingId)->order;
               $countOrderStock = $order->orderStocks->count();
               $countOrderSku = $order->orderSkus->count();
               // kiểm tra đơn xem đã chọn vị trí chưa nếu chưa sẽ thông báo lỗi
               if ($countOrderStock != $countOrderSku){
                   $this->errors()->add('order_inspected', static::ERROR_INVALID);
                   return;
               }
           }
           $processedOrderExportings = OrderExporting::query()->whereIn('id', $orderExportingIds)
               ->where('status', '<>', OrderExporting::STATUS_NEW)
               ->with(['order', 'freightBill'])
               ->get();
           if ($processedOrderExportings->count()) {
               $this->errors()->add('order_packing_ids', [
                   'code' => static::ERROR_INVALID,
                   'order_packings' => $processedOrderExportings->map(function (OrderExporting $orderExporting) {
                       return [
                           'order' => $orderExporting->order ? $orderExporting->order->code : '',
                           'freight_bill' => $orderExporting->freightBill ? $orderExporting->freightBill->freight_bill_code : '',
                       ];
                   })
               ]);
               return;
           }
       }else
       {
           $warehouseId = $this->input('warehouse_id', 0);
           if (!$this->warehouse = Warehouse::find($warehouseId)) {
               $this->errors()->add('warehouse_id', static::ERROR_EXISTS);
               return;
           }

           if ($documentPackingCode = $this->input('document_packing')) {
               $tenantId = $this->input('tenant_id', 0);
               if (empty(Document::query()->where([
                   'tenant_id' => $tenantId,
                   'code' => $documentPackingCode
               ])->first())) {
                   $this->errors()->add('document_packing', static::ERROR_EXISTS);
                   return;
               }
           }
           $orderExportingIds = $this->input('order_exporting_ids');
           foreach ($orderExportingIds as $orderExportingId) {
               $order           = OrderExporting::find($orderExportingId['id'])->order;
               $countOrderStock = $order->orderStocks->count();
               $countOrderSku   = $order->orderSkus->count();
               // kiểm tra đơn xem đã chọn vị trí chưa nếu chưa sẽ thông báo lỗi
               if ($countOrderStock != $countOrderSku) {
                   $this->errors()->add('order_inspected', static::ERROR_INVALID);
                   return;
               }
               $processedOrderExportings = OrderExporting::query()->where('id', $orderExportingId['id'])
                   ->where('status', '<>', OrderExporting::STATUS_NEW)
                   ->with(['order', 'freightBill'])
                   ->get();
               if ($processedOrderExportings->count()) {
                   $this->errors()->add('order_packing_ids', [
                       'code' => static::ERROR_INVALID,
                       'order_packings' => $processedOrderExportings->map(function (OrderExporting $orderExporting) {
                           return [
                               'order' => $orderExporting->order ? $orderExporting->order->code : '',
                               'freight_bill' => $orderExporting->freightBill ? $orderExporting->freightBill->freight_bill_code : '',
                           ];
                       })
                   ]);
                   return;
               }

           }
       }
    }
}
