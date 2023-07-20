<?php

namespace Modules\Product\Controllers;

use App\Base\Controller;
use League\Fractal\Manager as FractalManager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item as FractalItem;
use Modules\Product\Models\SkuCombo;
use Modules\Product\Transformers\SkuComboTransformer;
use Modules\Product\Validators\CreateSkuCombosValidator;
use Modules\Product\Validators\DetailSkuComboValidator;
use Modules\Service;
use Modules\Warehouse\Transformers\WarehouseTransformerNew;

class SkuComboController extends Controller
{

    public function index()
    {
        $request          = $this->request()->all();
        $skuComboName     = data_get($request, 'name');
        $skuComboCode     = data_get($request, 'code');
        $skuComboStatus   = data_get($request, 'status');
        $skuCode          = data_get($request, 'sku_code');
        $merchantId       = data_get($request, 'merchant_id');
        $categoryId       = data_get($request, 'category_id');
        $perPage          = data_get($request, 'per_page', 50);
        $createdAtRequest = data_get($request, 'created_at', []);
        $createdAt = [
            'from' => data_get($createdAtRequest, 'from'),
            'to'   => data_get($createdAtRequest, 'to'),
        ];

        if ($perPage > 100) {
            // $perPage = 100;
        }
        $paginator = SkuCombo::select('sku_combos.*')
            ->merchant($merchantId)
            ->category($categoryId)
            ->skuComboCode($skuComboCode)
            ->skuCode($skuCode)
            ->skuComboName($skuComboName)
            ->skuComboStatus($skuComboStatus)
            ->createdAt($createdAt)
            ->orderBy('sku_combos.id', 'DESC')
            ->paginate($perPage);
        
        $skuCombos = $paginator->getCollection();
        $include   = data_get($request, 'include');
        $fractal   = new FractalManager();
        if ($include) {
            $fractal->parseIncludes($include);
        }
        $resource = new FractalCollection($skuCombos, new SkuComboTransformer);
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        $dataReturn = $fractal->createData($resource)->toArray();
        $dataReturn['pagination'] = [
            'current_page' => $dataReturn['meta']['pagination']['current_page'],
            'page_total'   => $dataReturn['meta']['pagination']['total_pages'],
            'per_page'     => $dataReturn['meta']['pagination']['per_page'],
            'total'        => $dataReturn['meta']['pagination']['total'],
        ];

        return $this->response()->success($dataReturn);

    }


    public function detail($id)
    {
        $validator = new DetailSkuComboValidator($id);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $skuCombo   = $validator->getSkuCombo();
        
        $include = data_get($this->request(), 'include');
        $fractal  = new FractalManager();
        if ($include) {
            $fractal->parseIncludes($include);
        }
        $resource = new FractalItem($skuCombo, new SkuComboTransformer);
        $dataReturn = $fractal->createData($resource)->toArray();

        return $this->response()->success($dataReturn);
    }

    /**
     * @param $skuCombo
     * @return JsonResponse
     */
    public function getLogs($id)
    {
        $validator = new DetailSkuComboValidator($id);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $skuCombo   = $validator->getSkuCombo();
        $user = $this->getAuthUser();

        $logs = Service::product()->getSkuComboLogs($skuCombo);

        return $this->response()->success(compact('logs'));
    }

}
