<?php

namespace Modules\Document\Jobs;

use App\Base\Job;
use Carbon\Carbon;
use Modules\Document\Models\Document;
use Modules\Document\Models\ImportingBarcode;
use Modules\PurchasingOrder\Models\PurchasingOrder;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\Service;
use Modules\SupplierTransaction\Commands\CalculateSupplierTransaction;
use Modules\SupplierTransaction\Models\SupplierTransaction;
use Modules\User\Models\User;

class AfterConfirmDocumentImportingJob extends Job
{
    public $connection = 'redis';
    public $queue = 'document_importing';

    /**
     * @var integer
     */
    protected $documentId = 0;

    /**
     * @var User
     */
    protected $userSystem;

    /**
     * AfterConfirmDocumentImportingJob constructor.
     * @param $documentId
     */
    public function __construct($documentId)
    {
        $this->documentId = $documentId;
    }

    public function handle()
    {
        $documentImporting = Document::find($this->documentId);
        if (!$documentImporting instanceof Document) return;

        $this->userSystem = Service::user()->getSystemUserDefault();

        $this->changeStatePurchasingOrder($documentImporting);

        $this->changeStatePurchasingPackage($documentImporting);
    }

    /**
     * @param Document $documentImporting
     */
    protected function changeStatePurchasingOrder(Document $documentImporting)
    {
        $tenant            = $documentImporting->tenant;
        $importingBarcodes = $documentImporting->importingBarcodes;
        foreach ($importingBarcodes as $importingBarcode) {
            $objectId        = $importingBarcode->object_id;
            $purchasingOrder = null;
            if ($importingBarcode->type == ImportingBarcode::TYPE_ORDER_CODE) {
                $purchasingOrder = $tenant->purchasingOrders()->firstWhere('id', $objectId);
            } else if ($importingBarcode->type == ImportingBarcode::TYPE_PACKAGE_CODE) {
                $purchasingPackage = $tenant->purchasingPackages()->firstWhere('id', $objectId);
                if (!$purchasingPackage instanceof PurchasingPackage) {
                    continue;
                }
                $purchasingOrder = $purchasingPackage->purchasingOrder;
            }

            if (!$purchasingOrder instanceof PurchasingOrder) {
                continue;
            }

            Service::purchasingOrder()->changeState($purchasingOrder, PurchasingOrder::STATUS_DELIVERED, $this->userSystem);
        }
    }

    /**
     * Chuyển các kiện nhập về đã nhập kho
     * @param Document $documentImporting
     */
    protected function changeStatePurchasingPackage(Document $documentImporting)
    {
        $tenant            = $documentImporting->tenant;
        $importingBarcodes = $documentImporting->importingBarcodes;
        foreach ($importingBarcodes as $importingBarcode) {
            $objectId          = $importingBarcode->object_id;
            $purchasingPackage = null;
            if (!in_array($importingBarcode->type, [ImportingBarcode::TYPE_PACKAGE_FREIGHT_BILL, ImportingBarcode::TYPE_PACKAGE_CODE])) {
                continue;
            }

            $purchasingPackage = $tenant->purchasingPackages()->firstWhere('id', $objectId);
            if (!$purchasingPackage instanceof PurchasingPackage) {
                continue;
            }

            $purchasingPackage->imported_at = Carbon::now();
            Service::purchasingPackage()->changeState($purchasingPackage, PurchasingPackage::STATUS_IMPORTED, $this->userSystem);
            /**
             * Tính toán công nợ supplier
             */
            (new CalculateSupplierTransaction($purchasingPackage, SupplierTransaction::TYPE_IMPORT))->handle();
        }
    }
}
