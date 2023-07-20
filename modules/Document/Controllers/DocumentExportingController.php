<?php

namespace Modules\Document\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Services\Permission;
use Modules\Document\Models\Document;
use Modules\Document\Validators\CheckingWarningDocumentExportingValidator;
use Modules\Document\Validators\CreatingDocumentExportingValidator;
use Modules\Document\Validators\DeletingDocumentExportingValidator;
use Modules\Document\Validators\DocumentExportingDetailValidator;
use Modules\Document\Validators\ExportingDocumentExportingValidator;
use Modules\Document\Validators\ListingDocumentExportingValidator;
use Modules\Document\Validators\UpdatingDocumentExportingValidator;
use Modules\FreightBill\Models\FreightBill;
use Modules\Service;

class DocumentExportingController extends Controller
{
    /**
     * Kiểm tra những yêu cầu xuất hàng không hợp lệ trước khi tạo chứng từ xuất hàng
     *
     * @return JsonResponse
     */
    public function checkWarning()
    {
        $inputs              = $this->requests->only([
            'warehouse_id',
            'document_packing',
            'receiver_name',
            'receiver_phone',
            'receiver_license',
            'partner',
            'order_packing_ids',
        ]);
        $inputs['tenant_id'] = $this->user->tenant_id;
        $validator           = new CheckingWarningDocumentExportingValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $document = $validator->getDocument();
        if($document instanceof Document) {
            $orderPackingIds = $document->orderPackings()->pluck('order_packings.id')->toArray();
        } else {
            $orderPackingIds = (isset($inputs['order_packing_ids'])) ? $inputs['order_packing_ids'] : [];
        }

        return $this->response()->success(Service::documentExporting()->checkingWarning($orderPackingIds));
    }

    /**
     * @return JsonResponse
     */
    public function create()
    {
        $inputs    = $this->requests->only([
            'warehouse_id',
            'barcode_type',
            'document_packing',
            'receiver_name',
            'receiver_phone',
            'receiver_license',
            'partner',
            'order_exporting_ids'
        ]);
        $scan['scan'] = $this->request()->input('scan');
        $inputs = array_merge($scan, $inputs);
        $validator = new CreatingDocumentExportingValidator(array_merge(['tenant_id' => $this->user->tenant_id], $inputs));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $document = Service::documentExporting()->create($validator->getWarehouse(), $inputs, $this->user);
        return $this->response()->success([
            'document_exporting' => $document,
            'order_exportings' => $document->orderExportings
        ]);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function detail($id)
    {
        $validator = new DocumentExportingDetailValidator(['id' => (int)$id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentExporting = $validator->getDocumentExporting();
        if (!Gate::check(Permission::OPERATION_HISTORY_EXPORT)
            && $documentExporting->creator_id != $this->user->id
            && $documentExporting->verifier_id != $this->user->id) {
            return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
        }
        return $this->response()->success(
            [
                'document_exporting' => $documentExporting,
                'order_exportings' => $documentExporting->orderExportings
            ]
        );
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function update($id)
    {
        $inputs    = $this->requests->only([
            'receiver_name',
            'receiver_phone',
            'receiver_license',
            'partner',
        ]);
        $validator = new UpdatingDocumentExportingValidator(array_merge(['id' => (int)$id], $inputs));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentExporting = $validator->getDocumentExporting();
        if (!Gate::check(Permission::OPERATION_HISTORY_EXPORT)
            && $documentExporting->creator_id != $this->user->id) {
            return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
        }

        return $this->response()->success(
            [
                'document_exporting' => Service::documentExporting()->update($validator->getDocumentExporting(), $inputs, $this->user),
                'order_exportings' => $documentExporting->orderExportings
            ]
        );
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function cancel($id)
    {
        $validator = new DeletingDocumentExportingValidator(['id' => (int)$id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentExporting = $validator->getDocumentExporting();
        if (!Gate::check(Permission::OPERATION_HISTORY_EXPORT)
            && $documentExporting->creator_id != $this->user->id) {
            return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
        }
        return $this->response()->success(
            [
                'document_exporting' => Service::documentExporting()->cancel($validator->getDocumentExporting(), $this->user),
                'order_exportings' => $documentExporting->orderExportings
            ]
        );
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function export($id)
    {
        $validator = new ExportingDocumentExportingValidator(['id' => (int)$id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentExporting = $validator->getDocumentExporting();
        if (!Gate::check(Permission::OPERATION_HISTORY_EXPORT)
            && $documentExporting->creator_id != $this->user->id) {
            return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
        }
        return $this->response()->success(
            [
                'document_exporting' => Service::documentExporting()->exporting($validator->getDocumentExporting(), $this->user),
                'order_exportings' => $documentExporting->orderExportings
            ]
        );
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filter     = $this->getQueryFilter();
        $validator  = new ListingDocumentExportingValidator($filter);
        if ($validator->fails())
            return $this->response()->error($validator);

        return $this->response()->success(Service::documentExporting()->listing($filter, $this->user));
    }

    /**
     * Tạo filter để query documents
     * @return array
     */
    protected function getQueryFilter()
    {
        $request = array_merge(ListingDocumentExportingValidator::$keyRequests, ['freight_bill_code']);
        $filter = $this->request()->only($request);
        $filter = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);

        $filter = $this->makeFilterOrderExportId($filter);
        $filter['tenant_id'] = $this->user->tenant_id;
        $filter['type'] = Document::TYPE_EXPORTING;
        $filter['warehouse_ids'] = $this->user->userWarehouses()->pluck('warehouse_id')->toArray();

        return $filter;
    }


    /**
     * @param array $filter
     * @return array
     */
    protected function makeFilterOrderExportId($filter = [])
    {
        $code = Arr::pull($filter, 'freight_bill_code', '');
        if(empty($code)) {
            return $filter;
        }

        $code = trim($code);

        $freightBill = FreightBill::query()->where('tenant_id', $this->user->tenant_id)
            ->where('freight_bill_code', $code)
            ->first();

        $filter['order_id'] = ($freightBill instanceof FreightBill) ? $freightBill->order_id : 0;
        return $filter;
    }

}
