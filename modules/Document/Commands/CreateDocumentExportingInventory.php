<?php

namespace Modules\Document\Commands;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Document\Models\Document;
use Modules\FreightBill\Models\FreightBill;
use Modules\Service;
use Modules\User\Models\User;

class CreateDocumentExportingInventory
{
    /** @var Document */
    protected $documentExporting;
    protected $barcodes;
    protected $uncheckBarcodes;
    protected $creator;

    /**
     * CreateDocumentExportingInventory constructor.
     * @param Document $documnetExporting
     * @param array $inputs
     * @param User $creator
     */
    public function __construct(Document $documnetExporting, array $inputs, User $creator)
    {
        $this->documentExporting = $documnetExporting;
        $this->barcodes          = Arr::get($inputs, 'barcodes', []);
        $this->uncheckBarcodes   = Arr::get($inputs, 'unckeck_barcodes', []);
        $this->creator           = $creator;
    }

    /**
     * @return Document
     */
    public function handle()
    {
        $this->barcodes = array_unique($this->barcodes);
        $info           = $this->documentExporting->info;
        $barcodeType    = Document::DOCUMENT_BARCODE_TYPE_FREIGHT_BILL;
        if (!empty($info[Document::INFO_DOCUMENT_EXPORTING_BARCODE_TYPE])) {
            $barcodeType = $info[Document::INFO_DOCUMENT_EXPORTING_BARCODE_TYPE];
        }

        $documentBarcodes = [];
        foreach ($this->documentExporting->orderExportings as $orderExporting) {
            if ($barcodeType == Document::DOCUMENT_BARCODE_TYPE_FREIGHT_BILL) {
                $documentBarcodes[$orderExporting->freightBill->freight_bill_code] = $orderExporting->id;
            } else {
                $documentBarcodes[$orderExporting->order->code] = $orderExporting->id;
            }
        }

        $intersectBarcodes = array_intersect($this->barcodes, array_keys($documentBarcodes));
        $diffBarcodes      = array_diff($this->barcodes, array_keys($documentBarcodes));

        return DB::transaction(function () use ($intersectBarcodes, $diffBarcodes, $documentBarcodes) {
            /**
             * Tạo chứng từ đối soát chứng từ xuất
             */
            $initInputs = [
                'type' => Document::TYPE_EXPORTING_INVENTORY,
                'info' => [
                    'document_exporting' => $this->documentExporting->code,
                    'document_exporting_id' => $this->documentExporting->id,
                ],
                'status' => Document::STATUS_COMPLETED,
                'verifier_id' => $this->creator->id,
                'verified_at' => Carbon::now(),
            ];
            $document   = Service::document()->create($initInputs, $this->creator, $this->documentExporting->warehouse);
            /**
             * Cập nhật vào bảng document_exporting_inventories
             */
            $orderInventories = [];
            $i                = 0;
            foreach ($intersectBarcodes as $intersectBarcode) {
                $i++;
                $orderInventories[$i]['document_id']           = $document->id;
                $orderInventories[$i]['document_exporting_id'] = $this->documentExporting->id;
                $orderInventories[$i]['order_exporting_id']    = $documentBarcodes[$intersectBarcode];
                $orderInventories[$i]['checked']               = true;
                $orderInventories[$i]['barcode']               = $intersectBarcode;
            }
            foreach ($this->uncheckBarcodes as $uncheckBarcode) {
                $i++;
                $orderInventories[$i]['document_id']           = $document->id;
                $orderInventories[$i]['document_exporting_id'] = $this->documentExporting->id;
                $orderInventories[$i]['checked']               = false;
                $orderInventories[$i]['order_exporting_id']    = $documentBarcodes[$uncheckBarcode];
                $orderInventories[$i]['barcode']               = $uncheckBarcode;
            }
            foreach ($diffBarcodes as $diffBarcode) {
                $i++;
                $orderInventories[$i]['document_id']           = $document->id;
                $orderInventories[$i]['document_exporting_id'] = $this->documentExporting->id;
                $orderInventories[$i]['checked']               = false;
                $orderInventories[$i]['order_exporting_id']    = 0;
                $orderInventories[$i]['barcode']               = $diffBarcode;
            }
            $document->documentOrderInventories()->createMany($orderInventories);
            /**
             * Cập nhật các vận đơn của các yêu cầu xuất sang đã xác nhận lấy hàng
             */
            $freightBillIds = $this->documentExporting->orderExportings->whereIn('id', $documentBarcodes)->pluck('freight_bill_id')->all();
            $freightBills   = FreightBill::query()->whereIn('id', $freightBillIds)->get();
            foreach ($freightBills as $freightBill) {
                Service::freightBill()->changeStatus($freightBill, FreightBill::STATUS_CONFIRMED_PICKED_UP, $this->creator);
            }

            /**
             * Cập nhật vào bảng document_orders
             */
            $document->orders()->sync($document->orderExportingInventories->pluck('order_id')->unique()->values()->all());

            return $document;
        });
    }
}
