<?php

namespace Modules\PurchasingOrder\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Services\Permission;
use Modules\PurchasingOrder\Transformers\PurchasingOrderDetailTransformer;
use Modules\PurchasingOrder\Validators\PurchasingOrderDetailValidator;
use Modules\PurchasingOrder\Validators\PurchasingOrderMappingVariantValidator;
use Modules\Service;

class PurchasingOrderController extends Controller
{
    /**
     * @param array $inputs
     * @return array
     */
    protected function getQueryFilter(array $inputs = [])
    {
        $inputs              = $inputs ?: [
            'code',
            'merchant_id',
            'purchasing_account_id',
            'marketplace',
            'supplier',
            'total_value',
            'ordered_at',
            'status',
            'sku_code',
            'tab_vendor',
            'sort',
            'sortBy',
            'page',
            'per_page',
        ];
        $filter              = $this->requests->only($inputs);
        $filter              = array_map(function ($value) {
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
        $filter    = $this->getQueryFilter();
        $tabVendor = Arr::get($filter, 'tab_vendor');
        /**
         * Hiển thị theo quyền view
         */
        if ($tabVendor || !Gate::check(Permission::MERCHANT_PURCHASING_ORDER_ALL)) {
            $filter['only_merchant_owner'] = true;
        }
        return $this->response()->success(Service::purchasingOrder()->listing($filter, $this->user));
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function detail($id)
    {
        $validator = new PurchasingOrderDetailValidator(['id' => (int)$id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $purchasingOrder = $validator->getPurchasingOrder();
        /**
         * Kiểm tra quyền
         */
        if (Gate::check(Permission::MERCHANT_PURCHASING_ORDER_ASSIGNED) && !Gate::check(Permission::MERCHANT_PURCHASING_ORDER_ALL)) {
            if (!in_array($purchasingOrder->merchant_id, $this->user->merchants->pluck('id')->all())) {
                return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
            }
        }
        $purchasingOrder->permission_views = Service::purchasingOrder()->pemissionViews($purchasingOrder, $this->user);
        return $this->response()->success(
            [
                'purchasing_order' => (new PurchasingOrderDetailTransformer())->transform($purchasingOrder),
            ]
        );
    }

    /**
     * @param $id
     * @param $purchasingVariantId
     * @return JsonResponse
     */
    public function purchasingVariantMapping($id, $purchasingVariantId)
    {
        $inputs                          = $this->requests->only(['sku_id']);
        $inputs['id']                    = (int)$id;
        $inputs['purchasing_variant_id'] = (int)$purchasingVariantId;
        $validator                       = new PurchasingOrderMappingVariantValidator($inputs);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $purchasingVariant = $validator->getPurchasingVariant();
        /**
         * Kiểm tra quyền
         */
        if (Gate::check(Permission::MERCHANT_SKU_MAP_ASSIGNED) && !Gate::check(Permission::MERCHANT_SKU_MAP_ALL)) {
            if (!in_array($validator->getPurchasingOrder()->merchant_id, $this->user->merchants->pluck('id')->all())) {
                return Service::app()->response()->error(403, ['message' => 'Unauthorized'], 403);
            }
        }

        Service::purchasingOrder()->mappingVariant($validator->getPurchasingOrder(), $purchasingVariant, $validator->getSku(), $this->user);

        return $this->response()->success([
            'purchasing_variant' => $purchasingVariant->refresh()
        ]);
    }

}
