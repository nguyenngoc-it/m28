<?php

namespace Modules\Product\Controllers;

use App\Base\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Services\Permission;
use Modules\Product\Models\Product;
use Modules\Product\Transformers\ProductDropShipListItemTransformer;

use Modules\Service;


class ProductDropShipController extends Controller
{
    /**
     * Tạo filter để query product
     * @param array $inputs
     * @return array
     */
    protected function getQueryFilter(array $inputs = [])
    {
        $inputs              = $inputs ?: [
            'id',
            'code',
            'name',
            'status',
            'merchant_id',
            'category_id',
            'unit_id',
            'ubox_product_code',
            'created_at',
            'page',
            'per_page',
            'sort',
            'sortBy',
            'nearly_sold_out',
        ];
        $filter              = $this->request()->only($inputs);
        $filter              = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);
        $filter['tenant_id'] = $this->user->tenant_id;
        $filter['dropship']  = true;
        if(empty($filter['status'])) {
            $filter['status'] = [
                Product::STATUS_WAITING_FOR_QUOTE,
                Product::STATUS_WAITING_CONFIRM,
                Product::STATUS_ON_SELL,
                Product::STATUS_STOP_SELLING
            ];
        }

        if (!$this->user->can(Permission::PRODUCT_MANAGE_ALL)) {
            $filter['merchant_ids'] = $this->user->merchants->pluck('id')->all();
        }

        if (!$this->user->can(Permission::OPERATION_VIEW_ALL_PRODUCT)) {
            $filter['supplier_id'] = $this->user->suppliers->pluck('id')->all();
        }

        return $filter;
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filter = $this->getQueryFilter();
        $results = Service::product()->listProduct($filter, $this->user);

        return $this->response()->success([
            'products' => array_map(function (Product $product) {
                return (new ProductDropShipListItemTransformer())->transform($product);
            }, $results->items()),
            'pagination' => $results,
        ]);
    }
}
