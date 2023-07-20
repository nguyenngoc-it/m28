<?php

namespace Modules\Stock\Controllers;

use App\Base\Controller;
use League\Fractal\Manager as FractalManager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use Modules\Stock\Models\Stock;
use Modules\Stock\Transformers\StockTransformer;

class StockExternalController extends Controller
{

    public function index()
    {
        $request         = $this->request()->all();
        $skuId           = data_get($request, 'sku_id');
        $skuCode         = data_get($request, 'sku_code');
        $skuName         = data_get($request, 'sku_name');
        $warehouseId     = data_get($request, 'warehouse_id');
        $warehouseAreaId = data_get($request, 'warehouse_area_id');
        $outOfStock      = data_get($request, 'out_of_stock');
        $merchantId      = data_get($request, 'merchant_id');
        $perPage         = data_get($request, 'per_page', 20);

        if ($perPage > 100) {
            $perPage = 100;
        }
        $paginator = Stock::query()
            ->skuId($skuId)
            ->SkuCode($skuCode)
            ->SkuName($skuName, $skuCode)
            ->WarehouseId($warehouseId)
            ->WarehouseAreaId($warehouseAreaId)
            ->OutOfStock($outOfStock)
            ->MerchantId($merchantId, $skuCode, $skuName)
            ->orderBy('stocks.id', 'DESC')
            ->paginate($perPage);
        $stocks    = $paginator->getCollection();
        $include   = data_get($request, 'include');
        $fractal   = new FractalManager();
        if ($include) {
            $fractal->parseIncludes($include);
        }
        $resource = new FractalCollection($stocks, new StockTransformer());
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        $dataReturn = $fractal->createData($resource)->toArray();

        return $this->response()->success($dataReturn);
    }
}
