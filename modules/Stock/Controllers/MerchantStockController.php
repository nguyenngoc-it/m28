<?php

namespace Modules\Stock\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Service;
use Modules\Stock\Models\Stock;

class MerchantStockController extends Controller
{
    /**
     * Lấy danh sách phí lưu kho theo ngày của 1 sku trong 1 stock cụ thể
     *
     * @param Stock $stock
     * @return JsonResponse
     */
    public function storageFeeDaily(Stock $stock)
    {
        $inputs  = $this->request()->only(['closing_time', 'page', 'per_page']);
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
}
