<?php

namespace Modules\Document\Commands;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentSkuImporting;
use Modules\Document\Models\ImportingBarcode;
use Modules\Product\Models\Sku;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\PurchasingPackage\Models\PurchasingPackageService;
use Modules\Service;
use Modules\Stock\Models\Stock;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;

class CreateDocumentImportingByPurchasingOrder
{
    /**
     * @var string
     */
    protected $barcodeType;

    /**
     * @var array
     */
    protected $objects;


    /**
     * @var Warehouse
     */
    protected $warehouse;

    /**
     * @var array
     */
    protected $skuData;

    /**
     * @var array
     */
    protected $inputs;

    /**
     * @var array
     */
    protected $purchasingPackageServices;


    /**
     * @var User
     */
    protected $creator;


    /**
     * CreateDocumentImporting constructor.
     * @param Warehouse $warehouse
     * @param array $skuData
     * @param array $objects
     * @param $inputs
     * @param User $creator
     */
    public function __construct(Warehouse $warehouse, $skuData = [], $objects = [], $inputs, User $creator)
    {
        $this->barcodeType               = Arr::get($inputs, 'barcode_type', '');
        $this->objects                   = $objects;
        $this->warehouse                 = $warehouse;
        $this->skuData                   = $skuData;
        $this->inputs                    = $inputs;
        $this->creator                   = $creator;
        $this->purchasingPackageServices = Arr::get($inputs, 'purchasingPackageServices', []);
    }

    /**
     * @return Document
     */
    public function handle()
    {
        return DB::transaction(function () {
            $document = $this->createDocument();

            $this->createImportingBarcode($document);
            $this->createDocumentSkuImporting($document);

            if (in_array($this->barcodeType, [ImportingBarcode::TYPE_PACKAGE_CODE, ImportingBarcode::TYPE_PACKAGE_FREIGHT_BILL])) {
                $this->updatePurchasingPackageServices($document);
            }

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
     */
    protected function createImportingBarcode(Document $document)
    {
        foreach ($this->objects as $object) {
            ImportingBarcode::create([
                'document_id' => $document->id,
                'tenant_id' => $document->tenant_id,
                'type' => $this->barcodeType,
                'barcode' => $object->code,
                'object_id' => $object->id
            ]);
        }
    }

    /**
     * Cập nhật lại dịch vụ trên kiện
     * @param Document $document
     */
    protected function updatePurchasingPackageServices(Document $document)
    {
        foreach ($this->objects as $object) {
            /** @var PurchasingPackage $purchasingPackage */
            $purchasingPackage = $this->creator->tenant->purchasingPackages()->firstWhere('id', $object->id);
            if (!$purchasingPackage instanceof PurchasingPackage) {
                continue;
            }

            $purchasingPackageServicesNewIds = [];
            foreach ($this->purchasingPackageServices as $purchasingPackageService) {
                $servicePrice                      = $purchasingPackageService['servicePrice'];
                $purchasingPackageServicesNewIds[] = $servicePrice->id;
            }

            //xóa các dịch vụ cũ của kiện nếu giao dịch viên không tích chọn nữa
            $purchasingPackage->purchasingPackageServices()->whereNotIn('service_price_id', $purchasingPackageServicesNewIds)->delete();

            foreach ($this->purchasingPackageServices as $purchasingPackageService) {
                $servicePrice = $purchasingPackageService['servicePrice'];
                $service      = $purchasingPackageService['service'];
                $skus         = $purchasingPackageService['skus'];
                $quantity     = 0;
                if ($skus) {
                    $quantity = (int)array_sum(array_column($skus, 'quantity'));
                }

                PurchasingPackageService::updateOrCreate([
                    'purchasing_package_id' => $purchasingPackage->id,
                    'service_id' => $service->id,
                    'service_price_id' => $servicePrice->id,
                ], [
                    'price' => $servicePrice->price,
                    'quantity' => $quantity,
                    'amount' => $quantity * $servicePrice->price,
                    'skus' => $skus
                ]);
            }

            $purchasingPackage->service_amount = $purchasingPackage->purchasingPackageServices()->sum('amount');
            $purchasingPackage->save();

            Service::purchasingPackage()->updateReceivedQuantityByDocument($purchasingPackage, $document);
        }
    }

    /**
     * @param Document $document
     */
    protected function createDocumentSkuImporting(Document $document)
    {
        foreach ($this->skuData as $skuData) {
            $sku              = $skuData['sku'];
            $skuId            = $sku->id;
            $quantity         = $skuData['quantity'];
            $receivedQuantity = $skuData['received_quantity'];

            $stock = Service::stock()->getPriorityStockWhenImportSku($this->warehouse, $sku);

            DocumentSkuImporting::create([
                'document_id' => $document->id,
                'tenant_id' => $document->tenant_id,
                'warehouse_id' => $this->warehouse->id,
                'warehouse_area_id' => $stock->warehouse_area_id,
                'sku_id' => $skuId,
                'quantity' => $quantity,
                'real_quantity' => $receivedQuantity,
                'stock_id' => ($stock instanceof Stock) ? $stock->id : 0,
            ]);

        }
    }

    /**
     * @param Sku $sku
     * @return int
     */
    protected function getQuantity(Sku $sku)
    {
        $purchasingVariants = $sku->purchasingVariants;
        if (empty($purchasingVariants)) {
            return 0;
        }

        $quantity = 0;
        foreach ($purchasingVariants as $purchasingVariant) {

            if ($this->barcodeType == ImportingBarcode::TYPE_ORDER_CODE) {
                $quantity += $purchasingVariant->purchasingOrderItems()->sum('received_quantity');
            } else {
                $quantity += $purchasingVariant->purchasingPackageItems()->sum('quantity');
            }
        }


        return $quantity;
    }
}
