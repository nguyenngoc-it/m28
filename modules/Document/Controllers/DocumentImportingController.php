<?php /** @noinspection ALL */

namespace Modules\Document\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Services\Permission;
use Modules\Document\Commands\CreateDocumentImporting;
use Modules\Document\Commands\CreateDocumentImportingByPurchasingOrder;
use Modules\Document\Models\DocumentSkuImporting;
use Modules\Document\Transformers\DocumentTransformer;
use Modules\Document\Validators\CancelDocumentImportingValidator;
use Modules\Document\Validators\ConfirmDocumentImportingValidator;
use Modules\Document\Validators\CreateDocumentImportingByPurchasingOrderValidator;
use Modules\Document\Validators\CreateDocumentImportingValidator;
use Modules\Document\Models\Document;
use Modules\Document\Validators\DocumentImportingDetailValidator;
use Modules\Document\Validators\DocumentListValidator;
use Modules\Document\Validators\PurchasingOrderImportingScanValidator;
use Modules\Document\Validators\SkuImportingScanValidator;
use Modules\Document\Validators\UpdatingDocumentImportingValidator;
use Modules\Document\Validators\UpdatingSkuImportingValidator;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\Service;

class DocumentImportingController extends Controller
{
    /**
     * @param $id
     * @return JsonResponse
     */
    public function detail($id)
    {
        $validator = new DocumentImportingDetailValidator(['id' => (int)$id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $document_importing = $validator->getDocumentImporting();
        if (!Gate::check(Permission::OPERATION_HISTORY_IMPORT)
            && $document_importing->verifier_id != $this->user->id) {
            return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
        }
        return $this->response()->success((new DocumentTransformer())->transform($document_importing));
    }

    /**
     * Tạo filter để query documents
     * @return JsonResponse
     */
    public function index()
    {
        $filter = $this->getQueryFilter();

        return $this->response()->success(Service::documentImporting()->listing($filter, $this->user));
    }

    /**
     * Tạo filter để query documents
     * @return array
     */
    protected function getQueryFilter()
    {
        $request  = array_merge(DocumentListValidator::$keyRequests, ['package_code', 'package_freight_bill_code']);
        $filter = $this->request()->only($request);
        $filter = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);

        $filter = $this->makeFilterPackageId($filter);
        $filter['tenant_id'] = $this->user->tenant_id;
        if (empty($filter['type'])) {
            $filter['imported_type'] = [Document::TYPE_IMPORTING, Document::TYPE_IMPORTING_RETURN_GOODS];
        } else {
            $filter['imported_type'] = $filter['type'];
            unset($filter['type']);
        }
        $filter['warehouse_ids'] = $this->user->userWarehouses()->pluck('warehouse_id')->toArray();

        return $filter;
    }

    /**
     * @param array $filter
     * @return array
     */
    protected function makeFilterPackageId($filter = [])
    {
        $packageCode = Arr::pull($filter, 'package_code', '');
        $packageFreightBillCode = Arr::pull($filter, 'package_freight_bill_code', '');
        if(empty($packageCode) && empty($packageFreightBillCode)) {
            return $filter;
        }

        $packageQuery = PurchasingPackage::query()->where('tenant_id', $this->user->tenant_id);
        if(!empty($packageCode)) {
            $packageQuery->where('code', trim($packageCode));
        }
        if(!empty($packageFreightBillCode)) {
            $packageQuery->where('freight_bill_code', trim($packageFreightBillCode));
        }
        $package = $packageQuery->first();
        $filter['package_id'] = ($package instanceof PurchasingPackage) ? $package->id : 0;
        return $filter;
    }

    /**
     * @return JsonResponse
     */
    public function scan()
    {
        $inputs = $this->requests->only([
            'warehouse_id',
            'barcode',
            'barcode_type',
            'merchant_id'
        ]);

        $validator = new SkuImportingScanValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        return $this->response()->success($validator->getSkus());
    }

    /**
     * @return JsonResponse
     */
    public function create()
    {
        $inputs = $this->requests->only([
            'warehouse_id',
            'skus',
            'barcode_type',
            'sender_name',
            'sender_phone',
            'sender_license',
            'sender_partner'
        ]);
        $user   = $this->getAuthUser();

        $validator = new CreateDocumentImportingValidator($user, $inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $skuData  = $validator->getSkuData();
        $document = (new CreateDocumentImporting($inputs['barcode_type'], $validator->getWarehouse(), $skuData, $inputs, $user))->handle();

        return $this->response()->success(
            [
                'document' => $document
            ]
        );
    }

    /**
     * @return JsonResponse
     */
    public function scanByPurchasingOrder()
    {
        $inputs = $this->requests->only([
            'warehouse_id',
            'barcode',
            'barcode_type',
            'purchasing_order_id',
            'merchant_id'
        ]);
        $user   = $this->getAuthUser();

        $validator = new PurchasingOrderImportingScanValidator($user, $inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        return $this->response()->success($validator->getSanData());
    }

    /**
     * @return JsonResponse
     */
    public function createByPurchasingOrder()
    {
        $inputs = $this->requests->only([
            'warehouse_id',
            'skus',
            'barcode_type',
            'object_ids',
            'services',
            'sender_name',
            'sender_phone',
            'sender_license',
            'sender_partner'
        ]);
        $user   = $this->getAuthUser();

        $validator = new CreateDocumentImportingByPurchasingOrderValidator($user, $inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $skuData  = $validator->getSkuData();
        $objects  = $validator->getObjects();
        $inputs['purchasingPackageServices'] = $validator->getServices();
        $document = (new CreateDocumentImportingByPurchasingOrder(
            $validator->getWarehouse(), $skuData, $objects, $inputs, $user)
        )->handle();

        return $this->response()->success(
            [
                'document' => $document
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
            'sender_name',
            'sender_phone',
            'sender_license',
            'sender_partner'
        ]);
        $validator = new UpdatingDocumentImportingValidator(array_merge(['id' => (int)$id], $inputs));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentImporting = $validator->getDocumentImporting();
        if (!Gate::check(Permission::OPERATION_HISTORY_IMPORT)
            && $documentImporting->creator_id != $this->user->id) {
            return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
        }

        return $this->response()->success(
            [
                'document' => Service::documentImporting()->update($documentImporting, $inputs, $this->user),
            ]
        );
    }

    /**
     * @param $skuImportId
     * @return JsonResponse
     */
    public function updateRealQuantity($skuImportId)
    {
        $creator              = $this->getAuthUser();
        $quantity             = $this->requests->get('real_quantity');
        $documentSkuImporting = DocumentSkuImporting::query()->firstWhere(['id' => $skuImportId, 'tenant_id' => $creator->tenant_id]);
        if (!$documentSkuImporting instanceof DocumentSkuImporting) {
            return $this->response()->error('INPUT_INVALID', ['id' => 'invalid']);
        }

        if (!is_numeric($quantity) || $quantity < 0) {
            return $this->response()->error('INPUT_INVALID', ['quantity' => 'invalid']);
        }

        $documentImporting = $documentSkuImporting->document;
        if (!Gate::check(Permission::OPERATION_HISTORY_IMPORT)
            && $documentImporting->creator_id != $this->user->id) {
            return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
        }
        $documentSkuImporting->real_quantity = $quantity;
        $documentSkuImporting->save();

        return $this->response()->success($documentSkuImporting);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function cancel($id)
    {
        $validator = new CancelDocumentImportingValidator(['id' => (int)$id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentImporting = $validator->getDocumentImporting();
        if (!Gate::check(Permission::OPERATION_HISTORY_IMPORT)
            && $documentImporting->creator_id != $this->user->id) {
            return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
        }
        return $this->response()->success(
            [
                'document' => Service::documentImporting()->cancel($documentImporting, $this->user),
            ]
        );
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function confirm($id)
    {
        $validator = new ConfirmDocumentImportingValidator(['id' => (int)$id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentImporting = $validator->getDocumentImporting();
        if (!Gate::check(Permission::OPERATION_HISTORY_IMPORT)
            && $documentImporting->creator_id != $this->user->id) {
            return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
        }
        return $this->response()->success(
            [
                'document' => Service::documentImporting()->confirm($documentImporting, $this->user),
            ]
        );
    }


    /**
     * @param $id
     * @return JsonResponse
     */
    public function skuImportings($id)
    {
        $creator           = $this->getAuthUser();
        $documentImporting = Document::query()->firstWhere(['id' => $id, 'tenant_id' => $creator->tenant_id]);
        if (!$documentImporting instanceof Document) {
            return $this->response()->error('INPUT_INVALID', ['id' => 'invalid']);
        }

        if (!Gate::check(Permission::OPERATION_HISTORY_IMPORT)
            && $documentImporting->creator_id != $this->user->id) {
            return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
        }

        $filter              = $this->request()->only(['sku_id', 'sku_code', 'sku_ref', 'warehouse_id', 'warehouse_area_id', 'sort_by', 'sort', 'page', 'per_page']);
        $filter              = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);
        $filter['tenant_id'] = $this->user->tenant_id;
        return $this->response()->success(Service::documentImporting()->listSkuImporting($documentImporting, $filter));
    }

    /**
     * Cập nhật hoặc tạo mới bản ghi lưu thông tin skus nhập kho
     *
     * @param $id
     * @return JsonResponse
     */
    public function updateOrCreateSkuImportings($id)
    {
        $inputs       = $this->requests->only([
            'skus'
        ]);
        $inputs['id'] = $id;
        $validator    = new UpdatingSkuImportingValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        Service::documentImporting()->updateOrCreateSkuImportings($validator->getDocumentImporting(), $validator->getSkus(), $this->user);
        $documentImporting   = $validator->getDocumentImporting()->refresh();
        $filter              = $this->request()->only(['sku_id', 'sku_code', 'sku_ref', 'warehouse_id', 'warehouse_area_id', 'sort_by', 'sort', 'page', 'per_page']);
        $filter              = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);
        $filter['tenant_id'] = $this->user->tenant_id;
        return $this->response()->success(Service::documentImporting()->listSkuImporting($documentImporting, $filter));
    }

}
