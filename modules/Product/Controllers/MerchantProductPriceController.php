<?php

namespace Modules\Product\Controllers;

use App\Base\Controller;
use Gobiz\Support\Helper;
use Illuminate\Http\JsonResponse;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductPrice;
use Modules\Product\Transformers\ProductPriceDetailTransformer;
use Modules\Product\Transformers\ProductPriceListItemTransformer;
use Modules\Product\Validators\DetailMerchantProductValidator;
use Modules\Service;

class MerchantProductPriceController extends Controller
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
     * @param $id
     * @return JsonResponse
     */
    public function detail($id)
    {
        /** @var ProductPrice $productPrice */
        $productPrice = ProductPrice::query()->where('tenant_id', $this->getAuthUser()->tenant_id)
            ->where('id', $id)->first();

        $validator = new DetailMerchantProductValidator(['id' => $productPrice->product_id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        return $this->response()->success((new ProductPriceDetailTransformer())->transform($productPrice));
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function active($id)
    {
        /** @var ProductPrice $productPrice */
        $productPrice = ProductPrice::query()->where('tenant_id', $this->getAuthUser()->tenant_id)
            ->where('id', $id)->first();

        $validator = new DetailMerchantProductValidator(['id' => $productPrice->product_id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $productPrice = Service::product()->activeProductPrice($productPrice, $this->getAuthUser());

        return $this->response()->success((new ProductPriceDetailTransformer())->transform($productPrice));
    }

    /**
     * @param $productId
     * @return JsonResponse
     */
    public function prices($productId)
    {
        $product = $this->user->tenant->products()->find($productId);
        if(!$product instanceof Product) {
            $this->response()->error('product_invalid' , []);
        }

        $validator = new DetailMerchantProductValidator(['id' => $product->id]);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }


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


}
