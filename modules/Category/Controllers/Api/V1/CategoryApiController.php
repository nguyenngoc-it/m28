<?php

namespace Modules\Category\Controllers\Api\V1;

use App\Base\ExternalController;
use Illuminate\Http\JsonResponse;
use Modules\Merchant\Models\Merchant;
use League\Fractal\Manager as FractalManager;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item as FractalItem;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Modules\Category\Models\Category;
use Modules\Category\Transformers\CategoryTransformerNew;

class CategoryApiController extends ExternalController
{
    /**
     * Listing Categories
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

            $paginator = Category::select('categories.*')
                ->tenant($merchant->tenant->id)
                ->code($code)
                ->orderBy('categories.id', 'DESC')
                ->paginate($perPage);

            $categories = $paginator->getCollection();

            $include = data_get($request, 'include');
            $fractal = new FractalManager();
            if ($include) {
                $fractal->parseIncludes($include);
            }
            $resource = new FractalCollection($categories, new CategoryTransformerNew);
            $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

            $dataReturn = $fractal->createData($resource)->toArray();
        }
        return $this->response()->success($dataReturn);

    }
}
