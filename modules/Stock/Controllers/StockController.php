<?php

namespace Modules\Stock\Controllers;

use App\Base\Controller;
use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Auth\Services\Permission;
use Modules\Service;
use Modules\Stock\Models\Stock;
use Modules\Stock\Transformers\StockListItemTransformer;
use Modules\Stock\Validators\ChangingStockValidator;
use Modules\Stock\Validators\ExportingStorageFeeValidator;
use Modules\Stock\Validators\ListStockValidator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use League\Fractal\Manager as FractalManager;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item as FractalItem;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Modules\Product\Models\BatchOfGood;
use Modules\Product\Transformers\BatchOfGoodsTransformer;
use Modules\Stock\Transformers\StockTransformer;

class StockController extends Controller
{
    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function import()
    {
        $input     = $this->request()->only(['file']);
        $validator = Validator::make($input, [
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ]);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $user   = $this->getAuthUser();
        $path   = Service::product()->getRealPathFile($input['file']);
        $errors = Service::stock()->importStocks($user->tenant, $path, $user);

        return $this->response()->success(compact('errors'));
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $input     = $this->request()->only(ListStockValidator::$keyRequests);
        $validator = new ListStockValidator($input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $user                   = $this->getAuthUser();
        $input['tenant_id']     = $user->tenant_id;
        $input['warehouse_ids'] = $this->user->userWarehouses()->pluck('warehouse_id')->toArray();
        if (!$this->user->can(Permission::OPERATION_VIEW_ALL_PRODUCT)) {
            $input['supplier_id'] = $this->user->suppliers->pluck('id')->toArray();
        }

        $results = Service::stock()->listStocks($input, $user);

        return $this->response()->success([
            'stocks' => array_map(function ($stock) {
                return (new StockListItemTransformer())->transform($stock);
            }, $results->items()),
            'pagination' => $results,
        ]);
    }

    public function groupByBatch()
    {
        // $input     = $this->request()->only(ListStockValidator::$keyRequests);
        // $input['groupBy'] = 'stocks.sku_id';
        // $validator = new ListStockValidator($input);
        // if ($validator->fails()) {
        //     return $this->response()->error($validator);
        // }

        // $user                   = $this->getAuthUser();
        // $input['tenant_id']     = $user->tenant_id;
        // $input['warehouse_ids'] = $this->user->userWarehouses()->pluck('warehouse_id')->toArray();

        // $paginator = Service::stock()->listStocks($input, $user);

        // $stocks    = $paginator->getCollection();

        $requestData = $this->request()->all();

        $include         = data_get($requestData, 'include');
        $perPage         = data_get($requestData, 'per_page');
        $skuId           = data_get($requestData, 'sku_id', 0);
        $skuCode         = data_get($requestData, 'sku_code');
        $skuName         = data_get($requestData, 'sku_name');
        $merchantId      = data_get($requestData, 'merchant_id');
        $warehouseId     = data_get($requestData, 'warehouse_id');
        $warehouseAreaId = data_get($requestData, 'warehouse_area_id');
        $outOfStock      = data_get($requestData, 'out_of_stock');

        $select = 'batch_of_goods.sku_id, 
                   MAX(batch_of_goods.sku_child_id) as sku_child_id,
                   MAX(batch_of_goods.updated_at) as updated_at';
        
        $paginator = BatchOfGood::select(DB::raw($select))
                                ->join('skus', 'batch_of_goods.sku_child_id', 'skus.id')
                                ->join('stocks', 'stocks.sku_id', 'skus.id')
                                ->skuId($skuId)
                                ->skuCode($skuCode)
                                ->skuName($skuName)
                                ->merchantId($merchantId)
                                ->warehouseId($warehouseId)
                                ->warehouseAreaId($warehouseAreaId)
                                ->outOfStock($outOfStock)
                                ->groupBy('batch_of_goods.sku_id')
                                ->orderBy(DB::raw('MAX(stocks.updated_at)'), 'DESC')
                                ->paginate($perPage);

        $batchOfGoods = $paginator->getCollection();

        $fractal = new FractalManager();
        if ($include) {
            $fractal->parseIncludes($include);
        }
        $resource = new FractalCollection($batchOfGoods, new BatchOfGoodsTransformer());
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        $dataReturn = $fractal->createData($resource)->toArray();

        $dataReturn['meta']['pagination']['page_total'] = data_get($dataReturn, 'meta.pagination.total_pages');

        return $this->response()->success($dataReturn);
    }


    /**
     * @return JsonResponse|BinaryFileResponse
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function export()
    {
        $input     = $this->request()->only(ListStockValidator::$keyRequests);
        $validator = new ListStockValidator($input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $user               = $this->getAuthUser();
        $input['tenant_id'] = $user->tenant_id;

        $pathFile = Service::stock()->export($input, $this->user);

        return (new BinaryFileResponse($pathFile))->deleteFileAfterSend(false);
    }

    /**
     * Download danh sách phí lưu kho sku theo ngày
     *
     * @return JsonResponse|BinaryFileResponse
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function exportStorageFee()
    {
        $inputs    = $this->request()->only([
            'merchant_id',
            'warehouse_id',
            'closing_time'
        ]);
        $validator = new ExportingStorageFeeValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $pathFile = Service::service()->exportStorageFeeDaily($inputs, $this->user);
        return (new BinaryFileResponse($pathFile))->deleteFileAfterSend(false);
    }

    /**
     * Lấy danh sách phí lưu kho theo ngày của 1 sku trong 1 stock cụ thể
     *
     * @param Stock $stock
     * @return JsonResponse
     */
    public function storageFeeDaily(Stock $stock)
    {
        $inputs  = $this->request()->only(['page', 'per_page', 'closing_time']);
        $filter  = array_merge($inputs, ['stock_id' => $stock->id]);
        $results = Service::stock()->storageFeeDaily($filter);

        return $this->response()->success([
            'sku_storage_fee_dailies' => array_map(function (Service\Models\StorageFeeSkuStatistic $storageFeeSkuStatistic) {
                $country = $storageFeeSkuStatistic->warehouse->country;
                return array_merge($storageFeeSkuStatistic->attributesToArray(), ['currency' => $country->currency]);
            }, $results->items()),
            'pagination' => $results,
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function changeWarehouseArea()
    {
        $input     = $this->request()->only([
            'warehouse_id',
            'warehouse_area_id',
            'stocks',
        ]);
        $validator = new ChangingStockValidator($input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        Service::stock()->changePositionStocks($validator->getDataStocks(), $this->user, $validator->getWarehouse());
        return $this->response()->success(['success' => true, 'warnings' => $validator->getErrorStocks()]);
    }
}
