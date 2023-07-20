<?php

namespace Modules\Product\Controllers;

use App\Base\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Modules\Product\Events\SkuIsGoodsBatchUpdated;
use Modules\Product\Models\Sku;
use Modules\Product\Validators\CreateBatchOfGoodsValidator;
use Modules\Product\Validators\IsGoodsBatchValidator;
use Modules\Service;

class BatchOfGoodsController extends Controller
{
    /**
     * @param Sku $sku
     * @return JsonResponse
     */
    public function IsGoodsBatch(Sku $sku)
    {
        $input     = $this->request()->only([
            'is_batch',
            'logic_batch',
        ]);
        $validator = new IsGoodsBatchValidator($input, $sku);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $sku->update($input);
        (new SkuIsGoodsBatchUpdated($sku, $this->user, Carbon::now(), $sku->getChanges()))->queue();

        return $this->response()->success(['sku' => $sku]);
    }

    /**
     * @param Sku $sku
     * @return JsonResponse
     */
    public function create(Sku $sku)
    {
        $input     = $this->request()->only([
            'code',
            'production_at',
            'expiration_at',
            'cost_of_goods'
        ]);
        $input     = array_map('trim', $input);
        $validator = new CreateBatchOfGoodsValidator($input, $sku);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $batchOfGood = Service::sku()->createBatchOfGood($sku, $input, $this->user);
        return $this->response()->success(['batch_of_good' => $batchOfGood]);
    }

    /**
     * @param Sku $sku
     * @return JsonResponse
     */
    public function index(Sku $sku)
    {
        return $this->response()->success(['batch_of_goods' => $sku->batchOfGoods]);
    }
}
