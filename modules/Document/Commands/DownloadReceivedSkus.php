<?php

namespace Modules\Document\Commands;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Modules\Document\Models\Document;
use Modules\Document\Models\ImportingBarcode;
use Rap2hpoutre\FastExcel\FastExcel;

class DownloadReceivedSkus
{
    /**
     * @var Document
     */
    protected $document;

    /**
     * DownloadReceivedSkus constructor.
     * @param Document $document
     */
    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    /**
     * @return array
     */
    function getImportingBarcodeSkus()
    {
        $importingBarcodeSkus = [];
        /** @var ImportingBarcode $importingBarcode */
        $i = 0;
        foreach ($this->document->importingBarcodes as $importingBarcode) {
            if (!empty($importingBarcode->snapshot_skus['skus'])) {
                foreach ($importingBarcode->snapshot_skus['skus'] as $skuItem) {
                    $i++;
                    $importingBarcodeSkus[$i] = [
                        'tracking_number' => $importingBarcode->snapshot_skus['freight_bill'],
                        'order_code' => $importingBarcode->snapshot_skus['order']['code'],
                        'sku' => $skuItem['code'],
                        'quantity' => $skuItem['quantity'],
                        'tracking_status' => trans($importingBarcode->freightBill->status),
                    ];
                }
            }
        }
        return $importingBarcodeSkus;
    }

    /**
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function handle()
    {
        return (new FastExcel($this->getImportingBarcodeSkus()))->export('importing-barcode-export.xlsx', function ($importingBarcodeSku) {
            return [
                trans('tracking_number') => $importingBarcodeSku['tracking_number'],
                trans('order_code') => $importingBarcodeSku['order_code'],
                trans('sku') => $importingBarcodeSku['sku'],
                trans('quantity') => $importingBarcodeSku['quantity'],
                trans('tracking_status') => $importingBarcodeSku['tracking_status'],
            ];
        });
    }
}
