<?php

namespace Modules\Warehouse\Controllers;

use App\Base\Controller;
use League\Fractal\Manager as FractalManager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use Modules\Order\Transformers\OrderTransformer;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Transformers\WarehouseTransformerNew;

class WarehouseExternalController extends Controller
{
    /** danh sách kho hàng
     * @return void
     */
    public function index()
    {
        $request       = $this->request()->all();
        $warehouseCode = data_get($request, 'warehouses_code');
        $warehouseName = data_get($request, 'warehouses_name');
        $countryId     = data_get($request, 'country_id');
        $status        = data_get($request, 'status');
        $perPage       = data_get($request, 'per_page', 20);

        if ($perPage > 100) {
            $perPage = 100;
        }
        $paginator = Warehouse::query()
            ->Code($warehouseCode)
            ->Name($warehouseName)
            ->Country($countryId)
            ->Status($status)
            ->orderBy('warehouses.id', 'DESC')
            ->paginate($perPage);
        $warehouses = $paginator->getCollection();
        $include = data_get($request, 'include');
        $fractal  = new FractalManager();
        if ($include) {
            $fractal->parseIncludes($include);
        }
        $resource = new FractalCollection($warehouses, new WarehouseTransformerNew);
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        $dataReturn = $fractal->createData($resource)->toArray();

        return $this->response()->success($dataReturn);

    }

}
