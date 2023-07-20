<?php

namespace Modules\InvalidOrder\Controllers;

use App\Base\Controller;
use App\Base\Validator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Modules\InvalidOrder\Models\InvalidOrder;
use Modules\Service;

class InvalidOrderController extends Controller
{
    /**
     * Tạo filter để query order
     * @param array $inputs
     * @return array
     */
    protected function getQueryFilter(array $inputs = [])
    {
        $inputs = $inputs ?: [
            'page',
            'per_page',
            'sort',
            'sort_by',
            'code',
            'error_code'
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
        $results = Service::invalidOrder()->listing($filter);

        return $this->response()->success([
            'invalid_orders' => $results->items(),
            'pagination' => $results,
        ]);
    }

    /**
     * @param InvalidOrder $invalidOrder
     * @return JsonResponse
     */
    public function resync(InvalidOrder $invalidOrder)
    {
        return $this->response()->success([
            'invalid_order' => $invalidOrder,
            'order' => Service::invalidOrder()->resync($invalidOrder)
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function resyncMulti()
    {
        $ids = $this->requests->get('ids');
        if (empty($ids) || !is_array($ids)) {
            return $this->response()->error(Validator::ERROR_INVALID, ['ids' => Validator::ERROR_INVALID]);
        }

        $invalidOrders = InvalidOrder::query()->whereIn('invalid_orders.id', $ids)->get();
        $results = [];
        if (!empty($invalidOrders)) {
            foreach ($invalidOrders as $row) {
                $results[] = [
                    'invalid_order' => $row,
                    'order' => Service::invalidOrder()->resync($row)
                ];
            }
        }

        return $this->response()->success($results);
    }

    public function delete(InvalidOrder $invalidOrder)
    {
        $invalidOrder->delete();
        return $this->response()->success([
            'invalid_order' => $invalidOrder
        ]);
    }
}
