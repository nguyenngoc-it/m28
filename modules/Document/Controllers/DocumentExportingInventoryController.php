<?php

namespace Modules\Document\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Document\Validators\CreatingDocumentExportingInventoryValidator;
use Modules\Document\Validators\DocumentExportingInventoryDetailValidator;
use Modules\Service;

class DocumentExportingInventoryController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function create()
    {
        $inputs    = $this->requests->only([
            'document_id',
            'barcodes',
            'uncheck_barcodes'
        ]);
        $validator = new CreatingDocumentExportingInventoryValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentExportinInventory = Service::documentExporting()->createInventory($validator->getDocumentExporting(), $inputs, $this->user);
        return $this->response()->success([
            'document_exporting_inventory' => $documentExportinInventory,
            'order_inventories' => $documentExportinInventory->documentOrderInventories
        ]);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function detail($id)
    {
        $validator = new DocumentExportingInventoryDetailValidator(['id' => (int)$id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentExportingInventory = $validator->getDocumentExportingInventory();
        return $this->response()->success(
            [
                'document_exporting_inventory' => $documentExportingInventory,
                'order_inventories' => $documentExportingInventory->documentOrderInventories
            ]
        );
    }
}
