<?php

namespace Modules\Product\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Modules\Product\Models\Sku;
use Modules\Product\Transformers\SkuListItemTransformer;
use Modules\Service;

class SkuExternalController extends SKUController
{
    /**
     * @return JsonResponse
     * @throws \Exception
     */
    public function importFobizSkuCode()
    {
        $input     = $this->request()->only(['file']);
        $validator = Validator::make($input, [
            'file' => 'required|file|mimes:' . config('upload.mimes') . '|max:' . config('upload.max_size'),
        ]);

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $user   = $this->getAuthUser();
        $errors = Service::product()->importFobizSkuCode($user, $input['file']);

        return $this->response()->success(compact('errors'));
    }

    /**
     * @return JsonResponse
     * @throws \Exception
     */
    public function index()
    {
        $filter             = $this->getQueryFilter();
        $filter['external'] = true;
        $results            = Service::product()->listSKUs($filter, $this->getAuthUser());

        return $this->response()->success([
            'skus' => array_map(function (Sku $product) {
                return (new SkuListItemTransformer())->transform($product);
            }, $results->items()),
            'pagination' => $results
        ]);
    }
}
