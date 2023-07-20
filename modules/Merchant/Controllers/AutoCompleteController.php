<?php /** @noinspection PhpMissingReturnTypeInspection */

namespace Modules\Merchant\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Product\Models\Sku;
use Modules\Product\Transformers\SkuTransformer;
use League\Fractal\Manager as FractalManager;
use League\Fractal\Resource\Collection as FractalCollection;
use Modules\Product\Models\SkuCombo;
use Modules\Product\Transformers\SkuComboTransformer;

class AutoCompleteController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function skuAll()
    {
        $dataRequest = $this->request()->all();
        $merchant = $this->user->merchant;

        $keyword = data_get($dataRequest, 'keyword');

        $dataReturn = [];

        $skus = Sku::select('skus.*')
                            ->leftJoin('store_skus', 'store_skus.sku_id', 'skus.id')
                            ->leftJoin('product_merchants', 'product_merchants.product_id', 'skus.product_id')
                            ->where(function($query) use ($keyword){
                                return $query->where('store_skus.code', "LIKE", "%" . $keyword . "%")
                                                ->orWhere('skus.code', "LIKE", "%" . $keyword . "%")
                                                ->orWhere('skus.name', 'LIKE', '%' . $keyword . '%');
                            })
                            ->where(function($query) use ($merchant){
                                return $query->where('skus.merchant_id', $merchant->id)
                                                ->orWhere('product_merchants.merchant_id', $merchant->id);
                            })
                            ->where('skus.status', Sku::STATUS_ON_SELL)
                            ->groupBy('skus.id')
                            ->take(50)
                            ->get();

        $include = data_get($dataRequest, 'include');
        $fractal  = new FractalManager();
        if ($include) {
            $fractal->parseIncludes($include);
        }
        $resource = new FractalCollection($skus, new SkuTransformer);

        $dataReturnSkus = $fractal->createData($resource)->toArray();
        $dataReturn['skus'] = $dataReturnSkus;

        $skuCombos = SkuCombo::select('sku_combos.*')
                            ->merchant($merchant->id)
                            ->keyword($keyword)
                            ->where('sku_combos.status', SkuCombo::STATUS_ON_SELL)
                            ->take(50)
                            ->get();

        $include = data_get($dataRequest, 'include');
        $fractal  = new FractalManager();
        if ($include) {
            $fractal->parseIncludes($include);
        }
        $resource = new FractalCollection($skuCombos, new SkuComboTransformer);

        $dataReturnSkuCombos = $fractal->createData($resource)->toArray();

        $dataReturn['sku_combos'] = $dataReturnSkuCombos;

        return $this->response()->success($dataReturn);
    }
}
