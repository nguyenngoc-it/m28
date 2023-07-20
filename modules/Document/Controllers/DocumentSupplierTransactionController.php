<?php

namespace Modules\Document\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Document\Models\Document;
use Modules\Document\Validators\CreateDocumentSupplierTransactionValidator;
use Modules\Service;

class DocumentSupplierTransactionController extends Controller
{
    /**
     * @param array $inputs
     * @return array
     */
    protected function getQueryFilter(array $inputs = []): array
    {
        $inputs              = $inputs ?: [
            'transaction_code',
            'amount',
            'creator_id',
            'supplier_id',
            'sort',
            'sortBy',
            'created_at',
            'page',
            'per_page',
            'payment_time'
        ];
        $filter              = $this->requests->only($inputs);
        $filter              = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);
        $filter['tenant_id'] = $this->user->tenant_id;

        return $filter;
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $document = Service::documentSupplierTransaction()->listing($this->getQueryFilter(), $this->user);
        return $this->response()->success($document);
    }


    /**
     * @param $supplierId
     * @return JsonResponse
     */
    public function create($supplierId): JsonResponse
    {
        $inputs    = $this->requests->only([
            'amount',
            'payment_time',
            'note',
            'transaction_code'
        ]);
        $inputs['tenant_id']   = intval($this->user->tenant_id);
        $inputs['supplier_id'] = intval($supplierId);
        $validator = new CreateDocumentSupplierTransactionValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $supplier = $validator->getSupplier();
        $result   = Service::documentSupplierTransaction()->create($supplier, $inputs, $this->user);
        return $this->response()->success($result);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function detail($id)
    {
        $document = Service::document()->query()->getQuery()->where('tenant_id', $this->user->tenant_id)
            ->where('id', intval($id))->first();
        if (!$document instanceof Document) {
            return $this->response()->error('INPUT_VALID', ['id' => 'invalid']);
        }

        return $this->response()->success(
            [
                'document' => $document,
                'supplier_transaction' => $document->documentSupplierTransaction,
            ]
        );
    }
}
