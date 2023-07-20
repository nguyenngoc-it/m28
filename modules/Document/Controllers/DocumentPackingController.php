<?php

namespace Modules\Document\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Services\Permission;
use Modules\Document\Commands\CreateDocumentPacking;
use Modules\Document\Validators\CreateDocumentPackingValidator;
use Modules\Document\Models\Document;
use Modules\Document\Validators\DocumentListValidator;
use Modules\Document\Validators\DocumentPackingDetailValidator;
use Modules\OrderPacking\Models\OrderPackingItem;
use Modules\Service;

class DocumentPackingController extends Controller
{
    /**
     * @param Document $documentPacking
     * @return bool
     */
    protected function checkPermissionView(Document $documentPacking)
    {
        if (!Gate::check(Permission::OPERATION_HISTORY_PREPARATION)
            && $documentPacking->verifier_id != $this->user->id) {
            return false;
        }

        return true;
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function detail($id)
    {
        $validator = new DocumentPackingDetailValidator(['id' => (int)$id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentPacking = $validator->getDocumentPacking();
        if (!$this->checkPermissionView($documentPacking)) {
            return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
        }

        return $this->response()->success(
            [
                'document_packing' => $documentPacking
            ]
        );
    }

    /**
     * Tạo filter để query documents
     * @return JsonResponse
     */
    public function index()
    {
        $filter = $this->getQueryFilter();

        return $this->response()->success(Service::documentPacking()->listing($filter, $this->user));
    }

    /**
     * Tạo filter để query documents
     * @return array
     */
    protected function getQueryFilter()
    {
        $filter = $this->request()->only(DocumentListValidator::$keyRequests);
        $filter = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);

        $filter['tenant_id']     = $this->user->tenant_id;
        $filter['type']          = Document::TYPE_PACKING;
        $filter['status']        = Document::STATUS_COMPLETED;
        $filter['warehouse_ids'] = $this->user->userWarehouses()->pluck('warehouse_id')->toArray();

        return $filter;
    }


    /**
     * @return JsonResponse
     */
    public function create()
    {
        $inputs = $this->requests->only([
            'warehouse_id',
            'order_packings',
            'scan_type',
        ]);
        $user   = $this->getAuthUser();

        $validator = new CreateDocumentPackingValidator($user->tenant, $inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $warehouse = $validator->getWarehouse();
        $document  = (new CreateDocumentPacking($warehouse, $validator->getOrderPackings(), $inputs, $user))->handle();

        return $this->response()->success(
            [
                'user' => $user->only(['id', 'username', 'name']),
                'orderPackings' => $validator->getOrderPackings(),
                'document' => $document
            ]
        );
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function orderPackings($id)
    {
        $validator = new DocumentPackingDetailValidator(['id' => (int)$id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentPacking = $validator->getDocumentPacking();
        if (!$this->checkPermissionView($documentPacking)) {
            return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
        }

        $input    = $this->request()->only(['page', 'per_page']);
        $page     = Arr::pull($input, 'page', config('paginate.page'));
        $per_page = Arr::pull($input, 'per_page', config('paginate.per_page'));
        $results  = $documentPacking->orderPackings()->withPivot(['created_at'])->paginate($per_page, ["*"], 'page', $page);

        return $this->response()->success(
            [
                'order_packings' => $results->items(),
                'pagination' => $results,
            ]
        );
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function skuStats($id)
    {
        $validator = new DocumentPackingDetailValidator(['id' => (int)$id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentPacking = $validator->getDocumentPacking();
        if (!$this->checkPermissionView($documentPacking)) {
            return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
        }
        $orderExportingStatus = $this->request()->get('order_exporting_status');

        $query = OrderPackingItem::query()->select(['skus.id as sku_id', 'skus.name as sku_name', 'skus.code as sku_code', DB::raw("SUM(order_packing_items.quantity) as order_packing_quantity")])
            ->join('skus', 'skus.id', '=', 'order_packing_items.sku_id')
            ->join('document_order_packings', 'document_order_packings.order_packing_id', '=', 'order_packing_items.order_packing_id');

        if (!empty($orderExportingStatus)) {
            $query->join('order_exportings', 'order_packing_items.order_packing_id', '=', 'order_exportings.order_packing_id');
            $query->where('order_exportings.status', trim(strtolower($orderExportingStatus)));
        }

        $skus = $query->where('document_order_packings.document_id', $documentPacking->id)
            ->groupBy('order_packing_items.sku_id')
            ->get();

        return $this->response()->success(
            [
                'skus' => $skus,
            ]
        );
    }
}
