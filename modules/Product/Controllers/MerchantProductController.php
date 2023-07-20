<?php /** @noinspection ALL */

namespace Modules\Product\Controllers;

use App\Base\Controller;
use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Gobiz\Support\Helper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Modules\Product\Events\ProductUpdated;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\Product\Transformers\MerchantProductDetailTransformer;
use Modules\Product\Transformers\MerchantSkuListItemTransformer;
use Modules\Product\Validators\MerchantCreateProductValidator;
use Modules\Product\Validators\UpdateMerchantProductValidator;
use Modules\Service;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MerchantProductController extends Controller
{
    /**
     * Tạo filter để query product
     * @param array $inputs
     * @return array
     */
    protected function getQueryFilter(array $inputs = [], array $extraInputs = [])
    {
        $inputs = $inputs ?: [
            'id',
            'code',
            'name',
            'keyword',
            'status',
            'created_at',
            'page',
            'per_page',
            'sku_codes',
            'sort',
            'sortBy',
            'nearly_sold_out',
            'not_yet_in_stock',
            'out_of_stock',
            'lack_of_export_goods',
            'warehouse_id'
        ];
        if ($extraInputs) {
            $inputs = array_merge($inputs, $extraInputs);
        }
        $filter              = $this->request()->only($inputs);
        $filter              = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);
        $filter['tenant_id'] = $this->user->tenant_id;
        $filter['dropship']  = false;

        return $filter;
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filter          = $this->getQueryFilter();
        $results         = Service::product()->listSellerSKUs($filter, $this->user);
        $queryAllResults = Service::product()->listSellerSKUs(array_merge($filter, ['export_data' => true]), $this->user);
        $warehouseId     = Arr::get($filter, 'warehouse_id', []);
        if ($warehouseId && !is_array($warehouseId)) {
            $warehouseId = [$warehouseId];
        }

        $statistics = $queryAllResults->get()->map(function (Sku $sku) use ($warehouseId) {
            $warehouseStocks = $warehouseId ? $sku->warehouseStocks->whereIn('warehouse_id', $warehouseId) : $sku->warehouseStocks;
            $stocks          = $warehouseId ? $sku->stocks->whereIn('warehouse_id', $warehouseId) : $sku->stocks;
            $realQuantity    = $warehouseStocks->sum('real_quantity');
            $packingQuantity = $warehouseStocks->sum('packing_quantity');
            return [
                'real_quantity' => $realQuantity,
                'packing_quantity' => $packingQuantity,
                'purchasing_quantity' => $warehouseStocks->sum('purchasing_quantity'),
                'available_inventory' => $realQuantity - $packingQuantity,
                'total_storage_fee' => $stocks->sum('total_storage_fee'),
            ];
        });

        return $this->response()->success([
            'products' => array_map(function (Sku $sku) use ($filter, $warehouseId) {
                $product = (new MerchantSkuListItemTransformer())->transform($sku, $warehouseId);
                if (!empty($product['warehouse_stocks'])) {
                    $warehouse_stocks = [];
                    foreach ($product['warehouse_stocks'] as $warehouse_stock) {
                        if (!$warehouse_stock['warehouse_status']) {
                            continue;
                        }

                        if (!empty($filter['lack_of_export_goods']) && $warehouse_stock['real_quantity_missing'] < 0) {
                            continue;
                        }

                        $warehouse_stocks[] = $warehouse_stock;
                    }
                    $product['warehouse_stocks'] = $warehouse_stocks;
                }

                return $product;
            }, $results->items()),
            'statistics' => [
                'real_quantity' => round($statistics->sum('real_quantity')),
                'packing_quantity' => round($statistics->sum('packing_quantity')),
                'purchasing_quantity' => round($statistics->sum('purchasing_quantity')),
                'available_inventory' => round($statistics->sum('available_inventory')),
                'total_storage_fee' => round($statistics->sum('total_storage_fee')),
            ],
            'pagination' => $results,
        ]);
    }


    /**
     * @return BinaryFileResponse
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function export()
    {
        $filter = $this->getQueryFilter();

        $pathFile = Service::product()->merchantExportSku($filter, $this->user);

        return (new BinaryFileResponse($pathFile))->deleteFileAfterSend(false);
    }

    /**
     * @return BinaryFileResponse
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function downloadStockIO()
    {
        $filter    = $this->getQueryFilter([], [
            'session'
        ]);
        $validator = Validator::make($filter, [
            'session' => 'required|array',
            'session.from' => 'required|date_format:Y-m-d H:i:s',
            'session.to' => 'required|date_format:Y-m-d H:i:s'
        ]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $pathFile = Service::product()->merchantDownloadStockIO($filter, $this->user);
        return (new BinaryFileResponse($pathFile))->deleteFileAfterSend(false);
    }

    /**
     * Nhập sản phẩm (sku) theo file
     */
    public function importExcel()
    {
        $input     = $this->request()->only(['file']);
        $validator = Validator::make($input, [
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $errors = Service::product()->importSellerProducts($input['file'], $this->user);

        return $this->response()->success(compact('errors'));
    }

    /**
     * @param $product
     * @return JsonResponse
     */
    public function detail(Product $product)
    {
        return $this->response()->success((new MerchantProductDetailTransformer())->transform($product));
    }

    /**
     * @param Product $product
     * @return JsonResponse
     */
    public function update(Product $product)
    {
        $input     = $this->requests->only([
            'name',
            'code',
            'category_id',
            'files',
            'removed_files',
            'services',
            'weight',
            'height',
            'width',
            'length'
        ]);
        $validator = new UpdateMerchantProductValidator($input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        Service::product()->updateMerchantProduct($product, $input, $this->user);

        return $this->response()->success((new MerchantProductDetailTransformer())->transform($product));
    }


    /**
     * @return JsonResponse
     */
    public function create()
    {
        $input = $this->requests->only([
            'name',
            'code',
            'category_id',
            'files',
            'services',
            'weight',
            'height',
            'width',
            'length'
        ]);
        if (empty($input['code'])) {
            $input['code'] = Helper::quickRandom(10, '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        }
        $validator = new MerchantCreateProductValidator($input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $product = Service::product()->merchantCreateProduct($input, $this->user);

        return $this->response()->success((new MerchantProductDetailTransformer())->transform($product));
    }

    /**
     * Ngừng bán sản phẩm
     *
     * @param Product $product
     * @return JsonResponse
     */
    public function stopSell(Product $product)
    {
        if ($product->merchant_id != $this->user->merchant->id) {
            return $this->response()->error(403, ['message' => 'Unauthorized'], 403);
        }
        $inputs    = $this->request()->only([
            'status'
        ]);
        $validator = Validator::make($inputs, [
            'status' => 'required|in:' . Product::STATUS_STOP_SELLING . ',' . Product::STATUS_ON_SELL,
        ]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $productOldStatus = $product->status;
        $product->status  = Arr::get($inputs, 'status');
        if ($product->isDirty(['status'])) {
            $product->save();
            $product->skus()->update(['status' => $product->status]);
            $payloadLogs['status']['old'] = $productOldStatus;
            $payloadLogs['status']['new'] = $product->status;
            (new ProductUpdated($product->id, $this->user->id, $payloadLogs))->queue();
        }

        return $this->response()->success((new MerchantProductDetailTransformer())->transform($product));
    }
}
