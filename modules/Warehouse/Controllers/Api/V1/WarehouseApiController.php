<?php

namespace Modules\Warehouse\Controllers\Api\V1;

use App\Base\ExternalController;
use Illuminate\Http\JsonResponse;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Product;
use League\Fractal\Manager as FractalManager;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item as FractalItem;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Modules\Product\Transformers\ProductTransformer;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Transformers\WarehouseTransformerNew;

class WarehouseApiController extends ExternalController
{
    /**
     * Listing Products By Merchant
     *
     * @return JsonResponse
     */
    public function index()
    {
        $request      = $this->request()->all();
        $perPage      = data_get($request, 'per_page');
        $merchantCode = data_get($request, 'merchant_code');
        $code         = data_get($request, 'code');

        $merchant = Merchant::where('code', $merchantCode)->first();

        $dataReturn = [];
        if ($merchant) {

            $paginator = Warehouse::select('warehouses.*')
                ->tenant($merchant->tenant->id)
                ->code($code)
                ->where('warehouses.country_id', $merchant->location_id)
                ->orderBy('warehouses.id', 'DESC')
                ->paginate($perPage);

            $warehouses = $paginator->getCollection();

            $include = data_get($request, 'include');
            $fractal = new FractalManager();
            if ($include) {
                $fractal->parseIncludes($include);
            }
            $resource = new FractalCollection($warehouses, new WarehouseTransformerNew);
            $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

            $dataReturn = $fractal->createData($resource)->toArray();
        }
        return $this->response()->success($dataReturn);

    }

    /**
     * Get Product Detail
     *
     * @param int $productId
     * @return JsonResponse
     */
    public function detail($productId)
    {
        $request = $this->request()->all();

        $merchantCode = data_get($request, 'merchant_code');

        $merchant = Merchant::where('code', $merchantCode)->first();

        $dataReturn = [];
        if ($merchant) {
            $creator   = $merchant->user;
            $creatorId = 0;
            if ($creator) {
                $creatorId = $creator->id;
            }

            $product = Product::select('products.*')
                ->ofCreator($creatorId)
                ->ofMerchant($merchant->id)
                ->where('id', $productId)
                ->first();

            $include = data_get($request, 'include');
            $fractal = new FractalManager();
            if ($include) {
                $fractal->parseIncludes($include);
            }
            $resource = new FractalItem($product, new ProductTransformer);

            $dataReturn = $fractal->createData($resource)->toArray();
        }

        return $this->response()->success($dataReturn);
    }
}
