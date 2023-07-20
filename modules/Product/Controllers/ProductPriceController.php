<?php

namespace Modules\Product\Controllers;

use App\Base\Controller;
use Gobiz\Support\Helper;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Services\Permission;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductPrice;
use Modules\Product\Transformers\ProductPriceDetailTransformer;
use Modules\Product\Transformers\ProductPriceListItemTransformer;
use Modules\Product\Validators\CreateProductPriceValidator;
use Modules\Service;

class ProductPriceController extends Controller
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
            'product_id',
            'type',
            'status',
            'created_at',
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

        return $filter;
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filter  = $this->getQueryFilter();
        $results = Service::product()->listProductPrices($filter, $this->user);

        return $this->response()->success([
            'product_prices' => array_map(function (ProductPrice $productPrice) {
                return (new ProductPriceListItemTransformer())->transform($productPrice);
            }, $results->items()),
            'pagination' => $results,
        ]);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function detail($id)
    {
        $productPrice = ProductPrice::query()->where('tenant_id', $this->getAuthUser()->tenant_id)
            ->where('id', $id)->first();

        return $this->response()->success((new ProductPriceDetailTransformer())->transform($productPrice));
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function cancel($id)
    {
        $productPrice = ProductPrice::query()->where('tenant_id', $this->getAuthUser()->tenant_id)
            ->where('id', $id)->first();

        $productPrice = Service::product()->cancelProductPrice($productPrice, $this->getAuthUser());

        return $this->response()->success((new ProductPriceDetailTransformer())->transform($productPrice));
    }

    /**
     * @param Product $product
     * @return JsonResponse
     */
    public function prices(Product $product)
    {
        $filter  = $this->getQueryFilter();
        $filter['product_id'] = $product->id;
        $results = Service::product()->listProductPrices($filter, $this->user);

        return $this->response()->success([
            'product_prices' => array_map(function (ProductPrice $productPrice) {
                return (new ProductPriceListItemTransformer())->transform($productPrice);
            }, $results->items()),
            'pagination' => $results,
        ]);
    }

    /**
     * @param Product $product
     * @return JsonResponse
     */
    public function create(Product $product)
    {
        $input = $this->requests->only([
            'type',
            'prices',
        ]);

        if(!$product->canCreatePrice() || !$this->getAuthUser()->can(Permission::QUOTATION_CREATE)) {
            return $this->response()->error(403, ['message' => 'Unauthorized'], 403);
        }

        $validator = new CreateProductPriceValidator($input, $product);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $productPrice = Service::product()->createProductPrice($product, $input, $this->user);

        return $this->response()->success((new ProductPriceDetailTransformer())->transform($productPrice));
    }

}
