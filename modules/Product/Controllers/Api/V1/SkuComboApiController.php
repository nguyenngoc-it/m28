<?php

namespace Modules\Product\Controllers\Api\V1;

use App\Base\ExternalController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\SkuCombo;
use League\Fractal\Manager as FractalManager;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item as FractalItem;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Modules\Marketplace\Services\Marketplace;
use Modules\Product\Commands\CreateSkuComboFrom3rdPartner;
use Modules\Product\Commands\UpdateSkuCombo;
use Modules\Product\Models\Sku;
use Modules\Product\Resource\Data3rdResource;
use Modules\Product\Transformers\SkuComboTransformer;
use Modules\Product\Validators\CreateSkuComboApiValidator;
use Modules\Product\Validators\CreateSkuCombosValidator;
use Modules\Product\Validators\DetailSkuComboValidator;
use Modules\Product\Validators\UpdateSkuComboApiValidator;
use Modules\Store\Models\Store;
use Modules\Store\Models\StoreSku;

class SkuComboApiController extends ExternalController
{
    /**
     * Listing Products By Merchant
     *
     * @return JsonResponse
     */
    public function index()
    {
        $request = $this->request()->all();
        $perPage      = data_get($request, 'per_page');
        $merchantCode = data_get($request, 'merchant_code');
        $skuComboCode = data_get($request, 'code');
        $skuCode      = data_get($request, 'sku_code');
        $SkuComboName = data_get($request, 'name');
        $createdFrom  = data_get($request, 'created_from');
        $createdTo    = data_get($request, 'created_to');

        $createdTimeRange = [
            'from' => $createdFrom,
            'to'   => $createdTo,
        ];

        $merchant = Merchant::where('code', $merchantCode)->where('tenant_id', $this->user->tenant->id)->first();

        $dataReturn = [];
        if ($merchant) {
            $paginator = SkuCombo::select('sku_combos.*')
                               ->merchant($merchant->id)
                               ->skuComboCode($skuComboCode)
                               ->skuCode($skuCode)
                               ->SkuComboName($SkuComboName)
                               ->createdAt($createdTimeRange)
                               ->orderBy('sku_combos.id', 'DESC')
                               ->paginate($perPage);
            
            $skuCombos = $paginator->getCollection();

            $include = data_get($request, 'include');
            $fractal  = new FractalManager();
            if ($include) {
                $fractal->parseIncludes($include);
            }
            $resource = new FractalCollection($skuCombos, new SkuComboTransformer);
            $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

            $dataReturn = $fractal->createData($resource)->toArray();
        } 
        return $this->response()->success($dataReturn);
    }

    public function create()
    {
        $requestData = $this->request()->all();
        
        $merchantCode = data_get($requestData, 'merchant_code');
        $items     = data_get($requestData, 'skus', []);

        $merchant = Merchant::where('code', $merchantCode)->where('tenant_id', $this->user->tenant->id)->first();

        $dataReturn = [];
        if ($merchant) {

            $validator = new CreateSkuComboApiValidator($requestData, $merchant);
            if ($validator->fails()) {
                return $this->response()->error($validator);
            }

            $itemSkus = [];

            foreach ($items as $item) {

                $code     = data_get($item, 'code');
                $price    = data_get($item, 'price');
                $quantity = data_get($item, 'quantity');
    
                // Check Sku Đã tồn tại trên hệ thống chưa
                $storeSku = StoreSku::select('store_skus.*')
                                        ->join('skus', 'store_skus.sku_id', 'skus.id')
                                        ->leftJoin('product_merchants', 'product_merchants.product_id', 'skus.product_id')
                                        ->where('store_skus.code', $code)
                                        ->where(function($query) use($merchant) {
                                            return $query->where('skus.merchant_id', $merchant->id)
                                                         ->orWhere('product_merchants.merchant_id', $merchant->id);
                                        })
                                        ->first();
                
                if ($storeSku) {
                    $itemSkus[] = [
                        'id'       => $storeSku->sku_id,
                        'price'    => $price,
                        'quantity' => $quantity,
                    ];
                }
            }

            $store = Store::where('marketplace_code', Marketplace::CODE_VELAONE)->first();

            $dataResource = new Data3rdResource;

            $dataResource->merchant_id      = $merchant->id;
            $dataResource->marketplace_code = $store->marketplace_code;
            $dataResource->name             = data_get($requestData, 'name', '');
            $dataResource->code             = data_get($requestData, 'code', '');
            $dataResource->category_id      = data_get($requestData, 'category_id', 0);
            $dataResource->source           = data_get($requestData, 'source', '');
            $dataResource->price            = data_get($requestData, 'price', 0);
            $dataResource->items            = $itemSkus;

            // dd($dataResource);

            $skuComboData = (new CreateSkuComboFrom3rdPartner($store, $dataResource))->handle();

            $fractal  = new FractalManager();
            $resource = new FractalItem($skuComboData, new SkuComboTransformer);

            $dataReturn = $fractal->createData($resource)->toArray();

        }

        return $this->response()->success($dataReturn);
        
    }

    public function update($id)
    {
        $request = $this->request()->all();
        $merchantCode = data_get($request, 'merchant_code');
        $user = Auth::user();

        $merchant = Merchant::where('code', $merchantCode)->where('tenant_id', $this->user->tenant->id)->first();

        $dataReturn = [];
        if ($merchant) {
            $inputs   = $this->request()->only(CreateSkuCombosValidator::$acceptKeys);

            $validator = new DetailSkuComboValidator($id, $merchant->id);
            if ($validator->fails()) {
                return $this->response()->error($validator);
            }

            
            $skuCombo = $validator->getSkuCombo();

            $validator = new UpdateSkuComboApiValidator($inputs, $skuCombo, $merchant);
            if ($validator->fails()) {
                return $this->response()->error($validator);
            }

            $items = data_get($inputs, 'skus', []);
            $itemSkus = [];
            if ($items) {
                foreach ($items as $item) {

                    $code     = data_get($item, 'code');
                    $price    = data_get($item, 'price');
                    $quantity = data_get($item, 'quantity');
        
                    // Check Sku Đã tồn tại trên hệ thống chưa
                    $storeSku = StoreSku::select('store_skus.*')
                                            ->join('skus', 'store_skus.sku_id', 'skus.id')
                                            ->leftJoin('product_merchants', 'product_merchants.product_id', 'skus.product_id')
                                            ->where('store_skus.code', $code)
                                            ->where(function($query) use($merchant) {
                                                return $query->where('skus.merchant_id', $merchant->id)
                                                             ->orWhere('product_merchants.merchant_id', $merchant->id);
                                            })
                                            ->first();
                    
                    if ($storeSku) {
                        $itemSkus[] = [
                            'id'       => $storeSku->sku_id,
                            'quantity' => $quantity,
                        ];
                    }
                }
            }

            $inputs['skus'] = $itemSkus;

            $skuComboUpdated = (new UpdateSkuCombo($skuCombo, $inputs, $user))->handle();

            $fractal  = new FractalManager();
            $resource = new FractalItem($skuComboUpdated, new SkuComboTransformer);
            $dataReturn = $fractal->createData($resource)->toArray();   
        }

        return $this->response()->success($dataReturn);
    }
}