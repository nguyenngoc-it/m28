<?php

namespace Modules\Document\Commands;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentSkuImporting;
use Modules\Document\Models\ImportingBarcode;
use Modules\Product\Models\Sku;
use Modules\Service;
use Modules\Stock\Models\Stock;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;

class CreateDocumentImporting extends CreateDocumentImportingBase
{
    /**
     * @var array
     */
    protected $inputs;

    /**
     * CreateDocumentImporting constructor.
     * @param $barcodeType
     * @param Warehouse $warehouse
     * @param array $skuData
     * @param $inputs
     * @param User $creator
     */
    public function __construct($barcodeType, Warehouse $warehouse, $skuData, $inputs, User $creator)
    {
        parent::__construct($barcodeType, $warehouse, $skuData, $creator);
        $this->inputs = $inputs;
    }

    /**
     * @return Document
     */
    public function handle(): Document
    {
        return DB::transaction(function () {
            $document = $this->createDocument();
            $this->createDocumentSkuImporting($document);

            return $document;
        });
    }

    /**
     * @return Document
     * @throws Exception
     */
    protected function createDocument()
    {
        $inputs = $this->inputs;
        $input  = [
            'type' => Document::TYPE_IMPORTING,
            'status' => Document::STATUS_DRAFT,
            'info' => [
                Document::INFO_DOCUMENT_IMPORTING_SENDER_NAME => Arr::get($inputs, 'sender_name', ''),
                Document::INFO_DOCUMENT_IMPORTING_SENDER_PHONE => Arr::get($inputs, 'sender_phone', ''),
                Document::INFO_DOCUMENT_IMPORTING_SENDER_LICENSE => Arr::get($inputs, 'sender_license', ''),
                Document::INFO_DOCUMENT_IMPORTING_SENDER_PARTNER => Arr::get($inputs, 'sender_partner', ''),
            ],
        ];

        return Service::document()->create($input, $this->creator, $this->warehouse);
    }

    /**
     * @param Document $document
     * @param Sku $sku
     * @return ImportingBarcode
     */
    protected function createImportingBarcodeSku(Document $document, Sku $sku)
    {
        $barcode = '';
        switch ($this->barcodeType) {
            case ImportingBarcode::TYPE_SKU_CODE:
            {
                $barcode = $sku->code;
                break;
            }
            case ImportingBarcode::TYPE_SKU_REF:
            {
                $barcode = $sku->ref;
                break;
            }
        }

        return ImportingBarcode::create([
            'document_id' => $document->id,
            'tenant_id' => $document->tenant_id,
            'type' => $this->barcodeType,
            'barcode' => $barcode,
            'object_id' => $sku->id,
        ]);
    }

    /**
     * @param Document $document
     */
    protected function createDocumentSkuImporting(Document $document)
    {
        foreach ($this->skuData as $skuData) {
            $sku      = $skuData['sku'];
            $quantity = $skuData['quantity'];

            $this->createImportingBarcodeSku($document, $sku);
            $stock = Service::stock()->getPriorityStockWhenImportSku($this->warehouse, $sku);

            DocumentSkuImporting::create([
                'document_id' => $document->id,
                'tenant_id' => $document->tenant_id,
                'warehouse_id' => $this->warehouse->id,
                'warehouse_area_id' => $stock->warehouse_area_id,
                'sku_id' => $sku->id,
                'quantity' => $quantity,
                'real_quantity' => $quantity,
                'stock_id' => ($stock instanceof Stock) ? $stock->id : 0,
            ]);
        }
    }
}
