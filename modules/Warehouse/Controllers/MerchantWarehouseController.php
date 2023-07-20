<?php

namespace Modules\Warehouse\Controllers;

use App\Base\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Service;
use Illuminate\Http\JsonResponse;

class MerchantWarehouseController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filers              = $this->request()->only(
            [
                'warehouse_id',
                'keyword',
                'sort',
                'sort_by',
                'paginate',
                'page',
                'per_page'
            ]
        );
        $filers['tenant_id'] = $this->user->tenant_id;
        $filers['country_id'] = $this->user->merchant->location_id;
        $filers['select']    = ['warehouses.id', 'warehouses.code', 'warehouses.name'];
        $warehouses          = Service::warehouse()->lists($filers);
        if ($warehouses instanceof LengthAwarePaginator) {
            return $this->response()->success(
                [
                    'warehouses' => $warehouses->items(),
                    'paginate' => $warehouses
                ]
            );
        }

        return $this->response()->success(['warehouses' => $warehouses]);
    }
}
