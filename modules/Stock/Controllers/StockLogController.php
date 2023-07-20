<?php

namespace Modules\Stock\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Modules\Product\Models\Product;
use Modules\Service;
use Modules\Stock\Models\StockLog;
use Modules\Stock\Transformers\StockLogListItemTransformer;

class StockLogController extends Controller
{
    /**
     * Tạo filter để query product
     * @param array $inputs
     * @return array
     */
    protected function getQueryFilter(array $inputs = [])
    {
        $inputs              = $inputs ?: [
            'change',
            'action',
            'stock_id',
            'sku_id',
            'product_id',
            'object_type',
            'object_id',
            'creator_id',
            'created_at',
            'real_quantity',
            'page',
            'per_page',
            'sort',
            'sortBy',
        ];
        $filter              = $this->request()->only($inputs);
        $filter              = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);

        $filter['tenant_id'] = $this->user->tenant_id;
        $productId = Arr::pull($filter, 'product_id');
        if(!empty($productId) && empty($filter['sku_id'])) {
            $product = $this->user->tenant->products()->find($productId);
            $filter['sku_id'] = 0;
            if($product instanceof Product) {
                $filter['sku_id'] = $product->skus()->pluck('id')->toArray();
            }
        }


        return $filter;
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filter = $this->getQueryFilter();
        $results = Service::stock()->listStockLogs($filter, $this->user);

        return $this->response()->success([
            'stock_logs' => array_map(function (StockLog $stockLog) {
                return (new StockLogListItemTransformer())->transform($stockLog);
            }, $results->items()),
            'pagination' => $results,
        ]);
    }
}
