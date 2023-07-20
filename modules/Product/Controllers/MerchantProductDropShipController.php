<?php

namespace Modules\Product\Controllers;

use App\Base\Controller;
use Gobiz\Support\Helper;
use Illuminate\Http\JsonResponse;
use Modules\Product\Models\Product;
use Modules\Product\Services\ProductEvent;
use Modules\Product\Transformers\MerchantProductDetailTransformer;
use Modules\Product\Transformers\MerchantProductDropShipDetailTransformer;
use Modules\Product\Transformers\MerchantProductListItemTransformer;
use Modules\Product\Validators\DetailMerchantProductValidator;
use Modules\Product\Validators\MerchantCreateProductDropShipValidator;
use Modules\Product\Validators\MerchantUpdateProductStatusValidator;
use Modules\Product\Validators\UpdatingMerchantProductValidator;
use Modules\Service;

class MerchantProductDropShipController extends Controller
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
        $filter['tenant_id']   = $this->user->tenant_id;
        $filter['merchant_id'] = $this->user->merchant->id;
        $filter['dropship']    = true;

        return $filter;
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $filter  = $this->getQueryFilter();
        $results = Service::product()->listSellerProducts($filter, $this->user);

        return $this->response()->success([
            'products' => array_map(function (Product $product) {
                return (new MerchantProductListItemTransformer())->transform($product);
            }, $results->items()),
            'pagination' => $results,
            'currency' => $this->user->merchant->getCurrency()
        ]);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function detail($id)
    {
        $validator = new DetailMerchantProductValidator(array_merge($this->requests->all(), ['id' => $id]));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $merchantProduct = $validator->getMerchantProduct();
        $merchantProduct = (new MerchantProductDropShipDetailTransformer())->transform($merchantProduct);

        return $this->response()->success($merchantProduct);
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
            'options',
            'skus',
            'weight',
            'height',
            'width',
            'length',
            'source'
        ]);
        if(empty($input['code'])) {
            $input['code'] = Helper::quickRandom(10, '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        }
        $validator = new MerchantCreateProductDropShipValidator($input);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $input['options'] = $validator->getOptions();
        $input['skus']    = $validator->getSkus();

        $product = Service::product()->merchantCreateProductDropShip($input, $this->user);

        return $this->response()->success((new MerchantProductDetailTransformer())->transform($product));
    }


    /**
     * @param $id
     * @return JsonResponse
     */
    public function update($id)
    {
        $input     = $this->requests->only([
            'name',
            'code',
            'category_id',
            'files',
            'removed_files',
            'services',
            'options',
            'skus',
            'weight',
            'height',
            'width',
            'length',
            'source'
        ]);

        $validator = new UpdatingMerchantProductValidator(array_merge($input, ['id' => $id]));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        if($validator->getOptions() !== null) {
            $input['options'] = $validator->getOptions();
        }
        if($validator->getSkus() !== null) {
            $input['skus'] = $validator->getSkus();
        }

        $merchantProduct = $validator->getMerchantProduct();
        Service::product()->merchantUpdateProductDropShip($merchantProduct, $input, $this->user);

        return $this->response()->success((new MerchantProductDetailTransformer())->transform($merchantProduct));
    }



    /**
     * @return JsonResponse
     */
    public function updateStatus()
    {
        $user      = $this->getAuthUser();
        $input     = $this->request()->only(['ids', 'status']);
        $validator = new MerchantUpdateProductStatusValidator($user->merchant, $input);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $products = $validator->getProducts();
        foreach ($products as $product) {
            $productOld = clone $product;
            $product->status = $input['status'];
            $product->save();

            $product->logActivity(ProductEvent::UPDATE_STATUS, $user, [
                'from' => $productOld->status,
                'to' => $product->status,
            ]);

        }

        return $this->response()->success($products);
    }
}
