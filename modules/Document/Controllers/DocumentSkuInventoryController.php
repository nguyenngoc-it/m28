<?php

namespace Modules\Document\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Document\Validators\BalancingDocumentSkuInventoryValidator;
use Modules\Document\Validators\CompletingDocumentSkuInventoryValidator;
use Modules\Document\Validators\CreatingDocumentSkuInventoryValidator;
use Modules\Document\Validators\DocumentSkuInventoryDetailValidator;
use Modules\Document\Validators\DocumentSkuInventoryHistoryValidator;
use Modules\Document\Validators\ImportingSkuDocumentSkuInventoryValidator;
use Modules\Document\Validators\ScanningDocumentSkuInventoryValidator;
use Modules\Document\Validators\UpdatingDocumentSkuInventoryValidator;
use Modules\Service;

class DocumentSkuInventoryController extends Controller
{

    /**
     * @param array $inputs
     * @return array
     */
    protected function getQueryFilter(array $inputs = [])
    {
        $inputs                  = $inputs ?: [
            'code',
            'status',
            'warehouse_id',
            'sort',
            'sortBy',
            'page',
            'per_page',
        ];
        $filter                  = $this->requests->only($inputs);
        $filter                  = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);
        $filter['tenant_id']     = $this->user->tenant_id;
        $filter['warehouse_ids'] = $this->user->userWarehouses()->pluck('warehouse_id')->toArray();

        return $filter;
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        return $this->response()->success(Service::documentSkuInventory()->listing($this->getQueryFilter(), $this->user));
    }

    /**
     * @return JsonResponse
     */
    public function create()
    {
        $inputs    = $this->requests->only([
            'warehouse_id',
        ]);
        $validator = new CreatingDocumentSkuInventoryValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        return $this->response()->success([
            'document_sku_inventory' => Service::documentSkuInventory()->create($validator->getWarehouse(), $this->user)
        ]);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function detail($id)
    {
        $validator = new DocumentSkuInventoryDetailValidator(['id' => (int)$id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentInventory = $validator->getDocumentInventory();
        return $this->response()->success(
            [
                'document_sku_inventory' => $documentInventory,
                'sku_inventories' => $documentInventory->documentSkuInventories,
                'warehouse_areas' => $documentInventory->warehouse->areas,
            ]
        );
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function update($id)
    {
        $inputs       = $this->requests->only([
            'sku_id',
            'warehouse_area_id',
            'quantity',
            'explain',
            'note'
        ]);
        $inputs['id'] = (int)$id;
        $validator    = new UpdatingDocumentSkuInventoryValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        if ($validator->getSkuInventory()) {
            Service::documentSkuInventory()->updateSkuInventory($validator->getSkuInventory(), $inputs, $this->user);
        }
        $documentInventory = Service::documentSkuInventory()->update($validator->getDocumentSkuInventory(), $inputs, $this->user);
        return $this->response()->success(
            [
                'document_sku_inventory' => $documentInventory,
                'sku_inventories' => $documentInventory->documentSkuInventories
            ]
        );
    }

    /**
     * Quét sku để kiểm kê kho
     *
     * @param $id
     * @return JsonResponse
     */
    public function scan($id)
    {
        $inputs       = $this->requests->only([
            'barcode_type',
            'barcode',
            'quantity',
            'merchant_id',
            'warehouse_area_id'
        ]);
        $inputs['id'] = (int)$id;
        $validator    = new ScanningDocumentSkuInventoryValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentInventory = $validator->getDocumentSkuInventory();
        Service::documentSkuInventory()->scanSku($validator->getDocumentSkuInventory(), $validator->getWarehouseArea(), $validator->getSku(), $this->user, $validator->getQuantity());
        return $this->response()->success(
            [
                'document_sku_inventory' => $documentInventory->refresh(),
                'sku_inventories' => $documentInventory->documentSkuInventories
            ]
        );
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function importingSku($id)
    {
        $inputs       = $this->request()->only(['file', 'merchant_id']);
        $inputs['id'] = (int)$id;
        $validator    = new ImportingSkuDocumentSkuInventoryValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentSkuInventory = $validator->getDocumentSkuInventory();
        $merchant             = $validator->getMerchant();
        $errors               = Service::documentSkuInventory()->importSkuInventories($inputs['file'], $documentSkuInventory, $this->user, $merchant);

        return $this->response()->success([
            'errors' => $errors,
            'document_sku_inventory' => $documentSkuInventory->refresh(),
            'sku_inventories' => $documentSkuInventory->documentSkuInventories
        ]);
    }

    /**
     * Lịch sử quét mã kiểm kê
     *
     * @param $id
     * @return JsonResponse
     */
    public function scanHistory($id)
    {
        $validator = new DocumentSkuInventoryHistoryValidator(['id' => (int)$id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentInventory = $validator->getDocumentInventory();
        return $this->response()->success(
            [
                'inventory_scan_histories' => Service::documentSkuInventory()->scanHistories($documentInventory)
            ]
        );
    }

    /**
     * Cân bằng số lượng kiểm kê
     * Tạo các bản ghi nhập/xuất sku
     * Đồng thời xác nhận luôn chứng từ
     *
     * @param $id
     * @return JsonResponse
     */
    public function balance($id)
    {
        $inputs['id'] = (int)$id;
        $validator    = new BalancingDocumentSkuInventoryValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentSkuInventory = Service::documentSkuInventory()->balanceSkus($validator->getDocumentInventory(), $this->user);
        return $this->response()->success(
            [
                'document_sku_inventory' => $documentSkuInventory,
                'sku_inventories' => $documentSkuInventory->documentSkuInventories
            ]
        );
    }

    /**
     * Xác nhận kết thúc kiểm kê
     *
     * @param $id
     * @return JsonResponse
     */
    public function complete($id)
    {
        $inputs['id'] = (int)$id;
        $validator    = new CompletingDocumentSkuInventoryValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $documentSkuInventory = Service::documentSkuInventory()->completeDocument($validator->getDocumentInventory(), $this->user);
        return $this->response()->success(
            [
                'document_sku_inventory' => $documentSkuInventory,
                'sku_inventories' => $documentSkuInventory->documentSkuInventories
            ]
        );
    }

}
