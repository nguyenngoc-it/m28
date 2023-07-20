<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentOrder;
use Modules\Document\Models\ImportingBarcode;
use Modules\FreightBill\Models\FreightBill;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderSku;
use Modules\Service;
use Modules\Warehouse\Models\Warehouse;

class ScanningDocumentImportingReturnGoodsValidator extends Validator
{
    /** @var FreightBill $freightBill */
    protected $freightBill;

    /**
     * @var array
     */
    protected $documentImportingReturnGoods;

    public function rules()
    {
        return [
            'warehouse_id' => 'required',
            'barcode' => 'required',
            'barcode_type' => 'required|in:' . ImportingBarcode::TYPE_ORDER_CODE . ',' . ImportingBarcode::TYPE_FREIGHT_BILL,
        ];
    }

    /**
     * @return array
     */
    public function getDocumentImportingReturnGoods(): array
    {
        return $this->documentImportingReturnGoods;
    }

    /**
     * @return FreightBill
     */
    public function getFreightBill(): FreightBill
    {
        return $this->freightBill;
    }

    protected function customValidate()
    {
        $warehouseId = $this->input('warehouse_id');
        $barcode     = $this->input('barcode');
        $barcodeType = $this->input('barcode_type');
        /** @var Warehouse $warehouse */
        $warehouse = $this->user->tenant->warehouses()->firstWhere('id', $warehouseId);
        if (!$warehouse) {
            $this->errors()->add('warehouse_id', static::ERROR_EXISTS);
            return;
        }

        if (!$warehouseArea = $warehouse->getDefaultArea()) {
            $this->errors()->add('warehouse_area', static::ERROR_EXISTS);
            return;
        }

        $this->freightBill = $this->gettingFreightBill($barcodeType, $barcode, $warehouse);
        if (empty($this->freightBill)) {
            $this->errors()->add('freight_bill', static::ERROR_EXISTS);
            return;
        }
        if (in_array($this->freightBill->status, [FreightBill::STATUS_FAILED_PICK_UP, FreightBill::STATUS_RETURN_COMPLETED, FreightBill::STATUS_CANCELLED])) {
            $this->errors()->add('freight_bill', [
                'message' => static::ERROR_STATUS_INVALID,
                'status' => $this->freightBill->status
            ]);
            return;
        }
        /**
         * Nếu mã vận đơn đang nằm trên 1 phiếu nhập hoàn khác thì báo lỗi
         */
        $checkImportingBarcode = $this->freightBill->importingBarcodeReturnGoods();
        if ($checkImportingBarcode) {
            $this->errors()->add('freight_bill', [
                'message' => ($checkImportingBarcode->document->status == Document::STATUS_DRAFT) ? 'has_processing_importing' : 'has_finished_importing',
                'document' => $checkImportingBarcode->document,
            ]);
        }

        $order                              = $this->freightBill->order;
        $this->documentImportingReturnGoods = Service::documentImporting()->makeSnapshotReturnGoods($this->freightBill->order, $order->orderSkus->map(function (OrderSku $orderSku) {
            return ['id' => $orderSku->sku_id, 'quantity' => $orderSku->quantity];
        })->values()->all());
    }

    /**
     * @param string $barcodeType
     * @param string $barcode
     *
     * @param Warehouse $warehouse
     * @return FreightBill|mixed|null|void
     */
    protected function gettingFreightBill($barcodeType, $barcode, Warehouse $warehouse)
    {
        if ($barcodeType == ImportingBarcode::TYPE_ORDER_CODE) {
            /** @var Order|null $order */
            $order = Order::query()->where([
                'tenant_id' => $this->user->tenant->id,
                'code' => $barcode
            ])->first();
            if (empty($order)) {
                $this->errors()->add('barcode', 'not_found_order');
                return;
            }
            if ($order->status == Order::STATUS_CANCELED) {
                $this->errors()->add('order', static::ERROR_INVALID);
                return;
            }
            /**
             * Đơn chưa có ở bất kỳ chứng từ xuất kho đã xác nhận nào thì không thể nhập hoàn
             */
            $documentOrders = $order->documentOrders;
            $documents      = [];
            if ($documentOrders) {
                foreach ($documentOrders as $documentOrder) {
                    $document = $documentOrder->document;
                    if ($document->type == Document::TYPE_EXPORTING && $document->status == Document::STATUS_COMPLETED) {
                        $documents[] = $document;
                    }
                }
                if (empty($documents)) {
                    $this->errors()->add('document', static::ERROR_INVALID);
                    return;
                }
            }

            $orderPacking = $order->orderPacking;
            if (empty($orderPacking)) {
                $this->errors()->add('barcode', 'not_found_order_packing');
                return;
            }
            return $orderPacking->freightBill;
        }

        if ($barcodeType == ImportingBarcode::TYPE_FREIGHT_BILL) {
            $freightBills = FreightBill::query()->Where([
                'freight_bill_code' => $barcode,
                'tenant_id' => $this->user->tenant->id,
            ])->where('freight_bills.status', '<>', FreightBill::STATUS_CANCELLED)->get();
            if ($freightBills) {
                $dataFreightBill = [];
                foreach ($freightBills as $freightBill) {
                    $order = $freightBill->order;
                    if ($order) {
                        if ($order->status == Order::STATUS_CANCELED) {
                            continue;
                        }
                        /**
                         * Đơn chưa có ở bất kỳ chứng từ xuất kho đã xác nhận nào thì không thể nhập hoàn
                         */
                        $documentOrders = $order->documentOrders;
                        $documents      = [];
                        if ($documentOrders) {
                            foreach ($documentOrders as $documentOrder) {
                                $document = $documentOrder->document;
                                if ($document->type == Document::TYPE_EXPORTING && $document->status == Document::STATUS_COMPLETED) {
                                    $documents[] = $document;
                                }
                            }
                            if (empty($documents)) {
                                continue;
                            }
                        }
                    }
                    $dataFreightBill[] = $freightBill;
                }
                if (count($dataFreightBill) > 1){
                    $this->errors()->add('count', static::ERROR_INVALID);
                    return;
                }else{
                    if ($dataFreightBill){
                        return $dataFreightBill[0];
                    }else{
                        return null;
                    }
                }
            }
        }
        return null;
    }

}
