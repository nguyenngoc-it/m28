<?php

namespace Modules\WarehouseStock\Controllers;

use App\Base\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Service;
use Modules\WarehouseStock\Transformers\WarehouseStockListItemTransformer;
use Modules\WarehouseStock\Validators\ListWarehouseStockValidator;

class WarehouseStockController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $input = $this->request()->only(ListWarehouseStockValidator::$keyRequests);
        $validator = new ListWarehouseStockValidator($input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $user = $this->getAuthUser();
        $input['tenant_id'] = $user->tenant_id;
        $results = Service::warehouseStock()->listWarehouseStocks($input, $user);

        return $this->response()->success([
            'warehouse_stocks' => array_map(function ($warehouseStock) {
                return (new WarehouseStockListItemTransformer())->transform($warehouseStock);
            }, $results->items()),
            'pagination' => $results,
        ]);
    }
}
