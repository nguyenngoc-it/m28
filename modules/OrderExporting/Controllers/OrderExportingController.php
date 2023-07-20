<?php

namespace Modules\OrderExporting\Controllers;

use App\Base\Controller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Modules\OrderExporting\Validators\OrderExportingScanValidator;
use Modules\Service;

class OrderExportingController extends Controller
{
    /**
     * Tạo filter để query order
     * @param array $inputs
     * @return array
     */
    protected function getQueryFilter(array $inputs = [])
    {
        $inputs = $inputs ?: [
            'warehouse_id',
            'page',
            'per_page',
            'sort',
            'sort_by',
            'sort_by_ids',
            'paginate',
            'ids'
        ];
        $filter = $this->requests->only($inputs);
        $filter = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);

        $filter['tenant_id'] = $this->user->tenant_id;

        return $filter;
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filter = $this->getQueryFilter();
        if (empty($filter['warehouse_id'])) {
            return $this->response()->success([]);
        }
        $results = Service::orderExporting()->listing($filter);

        if ($results instanceof LengthAwarePaginator) {
            return $this->response()->success([
                'order_exportings' => $results->items(),
                'pagination' => $results,
            ]);
        }

        return $this->response()->success(
            ['order_exportings' => $results]
        );
    }

    /**
     * Quét xuất vận đơn tạo chứng từ xuất hàng
     *
     * @return JsonResponse
     */
    public function scan()
    {
        $inputs    = $this->requests->only([
            'warehouse_id',
            'barcode_type',
            'barcode',
        ]);
        $validator = new OrderExportingScanValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        return $this->response()->success(
            [
                'order_exporting' => $validator->getOrderExporting(),
            ]
        );
    }
}
