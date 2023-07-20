<?php

namespace Modules\Warehouse\Controllers;

use App\Base\Controller;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Modules\Service;
use Modules\Stock\Models\Stock;
use Modules\Stock\Transformers\StockListItemTransformer;
use Modules\Stock\Validators\ListStockValidator;
use Modules\Warehouse\Commands\ChangeStateWarehouse;
use Modules\Warehouse\Commands\CreateWarehouse;
use Modules\Warehouse\Commands\UpdateWarehouse;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;
use Modules\Warehouse\Transformers\WarehouseDetailTransformer;
use Modules\Warehouse\Transformers\WarehouseListItemTransformer;
use Modules\Warehouse\Validators\ChangeStateWarehouseValidator;
use Modules\Warehouse\Validators\CreateWarehouseValidator;
use Modules\Warehouse\Validators\CreateWarehouseAreaValidator;
use Modules\Warehouse\Validators\ListWarehouseValidator;
use Illuminate\Http\JsonResponse;
use Modules\Warehouse\Validators\UpdateWarehouseValidator;

class WarehouseController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filers             = $this->getQueryFilter();
        $filers['paginate'] = false;
        $filers['ids']      = $this->user->userWarehouses()->pluck('warehouse_id')->toArray();
        $warehouses         = Service::warehouse()->lists($filers);

        return $this->response()->success(['warehouses' => $warehouses]);
    }

    /**
     * @return JsonResponse
     */
    public function items()
    {
        $filers  = $this->getQueryFilter();
        $results = Service::warehouse()->lists($filers);

        return $this->response()->success([
            'warehouses' => array_map(function ($warehouse) {
                return (new WarehouseListItemTransformer())->transform($warehouse);
            }, $results->items()),
            'pagination' => $results,
        ]);
    }

    /**
     * @param Warehouse $warehouse
     * @return JsonResponse
     */
    public function detail(Warehouse $warehouse)
    {
        $data = (new WarehouseDetailTransformer())->transform($warehouse);
        return $this->response()->success($data);
    }


    /**
     * @param Warehouse $warehouse
     * @return JsonResponse
     */
    public function areas(Warehouse $warehouse)
    {
        $query = $warehouse->areas()
            ->orderByDesc('created_at');


        if (!empty($this->request()->get('merchant_id'))) {
            $merchant_id = trim($this->request()->get('merchant_id'));
            $query->whereHas('merchants', function (Builder $q) use ($merchant_id) {
                $q->where('merchants.id', $merchant_id);
            });
        }

        if (!empty($this->request()->get('id'))) {
            $query->where('id', trim($this->request()->get('id')));
        }

        if (!empty($this->request()->get('movable'))) {
            $query->where('movable', trim($this->request()->get('movable')));
        }

        if (!empty($this->request()->get('name'))) {
            $query->where('name', 'LIKE', '%' . trim($this->request()->get('name')) . '%');
        }

        if (!empty($this->request()->get('code'))) {
            $query->where('code', 'LIKE', '%' . trim($this->request()->get('code')) . '%');
        }

        if (!empty($this->request()->get('keyword'))) {
            $keyword = trim($this->request()->get('keyword'));
            $query->where(function (Builder $q) use ($keyword) {
                $q->where('code', 'LIKE', '%' . $keyword . '%');
                $q->orWhere('name', 'LIKE', '%' . $keyword . '%');
            });
        }

        $areas = $query
            ->whereNull('status')
            ->get()->map(function (WarehouseArea $warehouseArea) {
                return [
                    'warehouseArea' => $warehouseArea
                ];
            })->values()->all();

        return $this->response()->success(['areas' => $areas]);
    }


    /**
     * Tạo filter để query product
     * @return array
     */
    protected function getQueryFilter()
    {
        $filter = $this->request()->only(ListWarehouseValidator::$keyRequests);
        $filter = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);

        $filter['tenant_id'] = $this->getAuthUser()->tenant_id;

        if (!$this->request()->exists('status')) {
            $filter['status'] = true;
        }

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
     * @throws Exception
     */
    public function create()
    {
        $user      = $this->getAuthUser();
        $input     = $this->request()->all();
        $validator = (new CreateWarehouseValidator($user->tenant, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $warehouse = (new CreateWarehouse($user, $input))->handle();

        return $this->response()->success(['warehouse' => $warehouse]);
    }

    /**
     * @param Warehouse $warehouse
     * @return JsonResponse
     */
    public function update(Warehouse $warehouse)
    {
        $user      = $this->getAuthUser();
        $input     = $this->request()->all();
        $validator = (new UpdateWarehouseValidator($warehouse, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $warehouse = (new UpdateWarehouse($warehouse, $user, $input))->handle();
        $warehouse = (new WarehouseDetailTransformer())->transform($warehouse);
        return $this->response()->success($warehouse);
    }


    /**
     * @param Warehouse $warehouse
     * @return JsonResponse
     */
    public function changeState(Warehouse $warehouse)
    {
        $user      = $this->getAuthUser();
        $input     = $this->request()->all();
        $validator = (new ChangeStateWarehouseValidator($warehouse, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $warehouse = (new ChangeStateWarehouse($warehouse, $user, $input['status']))->handle();
        $warehouse = (new WarehouseDetailTransformer())->transform($warehouse);
        return $this->response()->success($warehouse);
    }

    /**
     * @param Warehouse $warehouse
     * @return JsonResponse
     */
    public function createArea(Warehouse $warehouse)
    {
        $user               = $this->getAuthUser();
        $input              = $this->request()->only(CreateWarehouseAreaValidator::$acceptKeys);
        $input['tenant_id'] = $warehouse->tenant_id;

        $validator = new CreateWarehouseAreaValidator($input, $warehouse, $user);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $input['warehouse_id'] = $warehouse->id;
        $warehouseArea         = WarehouseArea::create($input);
        return $this->response()->success(compact('warehouseArea'));
    }


    /**
     * @param Warehouse $warehouse
     * @return JsonResponse
     */
    public function stocks(Warehouse $warehouse)
    {
        $filter                 = $this->getQueryStockFilter();
        $filter['warehouse_id'] = $warehouse->id;
        $results                = Service::stock()->listStocks($filter, $this->getAuthUser());

        return $this->response()->success([
            'stocks' => array_map(function (Stock $stock) {
                return (new StockListItemTransformer())->transform($stock);
            }, $results->items()),
            'pagination' => $results
        ]);
    }

    /**
     * Tạo filter để query product
     * @return array
     */
    protected function getQueryStockFilter()
    {
        $filter = $this->request()->only(ListStockValidator::$keyRequests);
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
     */
    public function suggest()
    {
        $user      = $this->getAuthUser();
        $filter    = $this->request()->only(['keyword', 'limit']);
        $validator = Validator::make($filter, [
            'keyword' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $filter['tenant_id'] = $user->tenant_id;
        $filers['ids']       = $this->user->userWarehouses()->pluck('warehouse_id')->toArray();

        $results = Service::warehouse()->lists($filter, $user);

        return $this->response()->success([
            'warehouses' => array_map(function (Warehouse $warehouse) {
                return (new WarehouseListItemTransformer())->transform($warehouse);
            }, $results->items())
        ]);
    }

}
