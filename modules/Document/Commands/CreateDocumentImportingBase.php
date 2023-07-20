<?php

namespace Modules\Document\Commands;

use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentSkuImporting;
use Modules\Document\Models\ImportingBarcode;
use Modules\FreightBill\Models\FreightBill;
use Modules\Stock\Models\Stock;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;

abstract class CreateDocumentImportingBase
{
    /** @var Warehouse $warehouse */
    protected $warehouse;

    /** @var string */
    protected $barcodeType;
    /**
     * @var array
     */
    protected $skuData;
    /**
     * @var User
     */
    protected $creator;
    /**
     * @var array
     */
    protected $objects;
    protected $note;

    /**
     * CreateDocumentImportingBase constructor.
     * @param $barcodeType
     * @param Warehouse $warehouse
     * @param array $skuData
     * @param User $creator
     * @param array $objects
     * @param null $note
     */
    public function __construct($barcodeType, Warehouse $warehouse, array $skuData, User $creator, array $objects = [], $note = null)
    {
        $this->warehouse   = $warehouse;
        $this->barcodeType = $barcodeType;
        $this->skuData     = $skuData;
        $this->creator     = $creator;
        $this->objects     = $objects;
        $this->note        = $note;
    }

    /**
     * @param Document $document
     * @return void
     */
    protected function createImportingBarcode(Document $document)
    {
        foreach ($this->objects as $object) {
            if ($object instanceof FreightBill) {
                $object->code = $object->freight_bill_code;
            }
            ImportingBarcode::create([
                'document_id' => $document->id,
                'tenant_id' => $document->tenant_id,
                'type' => $this->barcodeType,
                'barcode' => $object->code,
                'object_id' => $object->id,
            ]);
        }
    }

    /**
     * @param Document $document
     */
    protected function createDocumentSkuImporting(Document $document)
    {
        foreach ($this->skuData as $skuData) {
            $sku              = $skuData['sku'];
            $receivedQuantity = $skuData['received_quantity'];
            $warehouseArea    = $this->warehouse->getDefaultArea();
            $stock            = Stock::query()->firstWhere(['warehouse_area_id' => $warehouseArea->id, 'sku_id' => $sku->id]);

            DocumentSkuImporting::create([
                'document_id' => $document->id,
                'tenant_id' => $document->tenant_id,
                'warehouse_id' => $this->warehouse->id,
                'warehouse_area_id' => $warehouseArea->id,
                'sku_id' => $sku->id,
                'quantity' => null,
                'real_quantity' => $receivedQuantity,
                'stock_id' => ($stock instanceof Stock) ? $stock->id : 0,
            ]);

        }
    }
}
