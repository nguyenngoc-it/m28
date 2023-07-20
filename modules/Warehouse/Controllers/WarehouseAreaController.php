<?php

namespace Modules\Warehouse\Controllers;

use App\Base\Controller;
use App\Base\Validator;
use Illuminate\Http\JsonResponse;
use Modules\Service;
use Modules\Stock\Models\Stock;
use Modules\Warehouse\Models\WarehouseArea;
use Modules\Warehouse\Transformers\WarehouseAreaListItemTransformer;
use Modules\Warehouse\Validators\UpdateWarehouseAreaValidator;

class WarehouseAreaController extends Controller
{
    /**
     * @param WarehouseArea $warehouseArea
     * @return JsonResponse
     */
    public function updateArea(WarehouseArea $warehouseArea)
    {
        $user      = $this->getAuthUser();
        $input     = $this->request()->only(UpdateWarehouseAreaValidator::$acceptKeys);
        $validator = new UpdateWarehouseAreaValidator($input, $warehouseArea, $user);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $warehouseArea->update($input);
        return $this->response()->success(compact('warehouseArea'));
    }

    /**
     * @param WarehouseArea $warehouseArea
     * @return JsonResponse
     */
    public function deleteArea(WarehouseArea $warehouseArea)
    {
        $stock = Stock::query()
            ->where('warehouse_area_id', $warehouseArea->id)
            ->where('real_quantity', '>', 0)
            ->where('quantity', '>', 0)
            ->get();
        if (count($stock) > 0) {
            return $this->response()->error('INPUT_INVALID', ['stock.quantity' => Validator::ERROR_INVALID]);
        }
        $warehouseArea->update(['status' => WarehouseArea::STATUS_HIDDEN]);
        return $this->response()->success($warehouseArea);
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filters              = $this->requests->only(['sort', 'sortBy', 'page', 'per_page', 'warehouse_id', 'movable', 'id']);
        $filters['tenant_id'] = $this->getAuthUser()->tenant_id;
        $filters['warehouse_ids'] = $this->user->userWarehouses()->pluck('warehouse_id')->toArray();

        $results = Service::warehouse()->listWarehouseArea($filters);

        return $this->response()->success([
            'warehouseAreas' => array_map(function (WarehouseArea $warehouseArea) {
                return (new WarehouseAreaListItemTransformer())->transform($warehouseArea);
            }, $results->items()),
            'pagination' => $results
        ]);
    }
}
