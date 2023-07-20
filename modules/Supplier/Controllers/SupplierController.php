<?php

namespace Modules\Supplier\Controllers;

use App\Base\Controller;
use Modules\Service;
use Modules\Supplier\Commands\CreateSupplier;
use Modules\Supplier\Commands\UpdateSupplier;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Transformers\SupplierDetailTransformer;
use Modules\Supplier\Transformers\SupplierListItemTransformer;
use Modules\Supplier\Validators\CreateSupplierValidator;
use Modules\Supplier\Validators\ListSupplierValidator;
use Illuminate\Http\JsonResponse;
use Modules\Supplier\Validators\UpdateSupplierValidator;
use Modules\SupplierTransaction\Transformers\SupplierTransactionTransformer;

class SupplierController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $filers  = $this->getQueryFilter();
        $results = Service::supplier()->lists($filers);

        return $this->response()->success([
            'suppliers' => array_map(function ($supplier) {
                return (new SupplierListItemTransformer())->transform($supplier);
            }, $results->items()),
            'pagination' => $results,
        ]);
    }


    /**
     * @param Supplier $supplier
     * @return JsonResponse
     */
    public function detail(Supplier $supplier)
    {
        $data = (new SupplierDetailTransformer())->transform($supplier);
        return $this->response()->success($data);
    }


    /**
     * Tạo filter để query product
     * @return array
     */
    protected function getQueryFilter()
    {
        $filter = $this->request()->only(ListSupplierValidator::$keyRequests);
        $filter = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);

        $filter['tenant_id'] = $this->getAuthUser()->tenant_id;

        if (
            $this->request()->get('created_at_from') &&
            $this->request()->get('created_at_to')
        ) {
            $filter['created_at'] = [
                'from' => $this->request()->get('created_at_from'),
                'to' => $this->request()->get('created_at_to'),
            ];
        }

        return $filter;
    }

    /**
     * @return JsonResponse
     * @throws \Exception
     */
    public function create()
    {
        $user      = $this->getAuthUser();
        $input     = $this->request()->all();
        $validator = (new CreateSupplierValidator($user->tenant, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $supplier = (new CreateSupplier($user, $input))->handle();

        return $this->response()->success(['supplier' => $supplier]);
    }

    /**
     * @param Supplier $supplier
     * @return JsonResponse
     */
    public function update(Supplier $supplier)
    {
        $user      = $this->getAuthUser();
        $input     = $this->request()->except(['code']);
        $validator = (new UpdateSupplierValidator($supplier, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $supplier = (new UpdateSupplier($supplier, $user, $input))->handle();
        $supplier = (new SupplierDetailTransformer())->transform($supplier);
        return $this->response()->success($supplier);
    }

    /** Lịch sử giao dịch của supplier
     * @param Supplier $supplier
     * @return JsonResponse
     */
    public function supplierTransactionHistory(Supplier $supplier)
    {
        $filers                = $this->getQueryFilter();
        $filers['supplier_id'] = $supplier->id;
        $results               = Service::supplierTransaction()->lists($filers);

        return $this->response()->success([
            'supplier_transactions' => array_map(function ($supplierTransaction) {
                return (new SupplierTransactionTransformer())->transform($supplierTransaction);
            }, $results->items()),
            'pagination' => $results
        ]);
    }

    /** Chi tiết công nợ
     * @param Supplier $supplier
     * @return JsonResponse
     * @throws \Gobiz\Support\RestApiException
     */
    public function infoWallets(Supplier $supplier)
    {
        $inventoryWallet       = $supplier->inventoryWallet()->detail();
        $soldWallet            = $supplier->soldWallet()->detail();
        $dataReturn = [
            "inventory" => $inventoryWallet,
            "sold" => $soldWallet,
        ];
        return $this->response()->success($dataReturn);
    }
}
