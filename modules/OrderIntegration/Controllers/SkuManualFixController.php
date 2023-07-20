<?php

namespace Modules\OrderIntegration\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Modules\OrderIntegration\Validators\UpdateSkuValidator;

class SkuManualFixController extends Controller
{
    /**
     * Sửa thông tin sku
     * @param $skuCode
     * @return JsonResponse
     */
    public function update($skuCode)
    {
        $input = $this->request()->only(['tenant_code','merchant_code', 'name']);
        $input['sku_code'] = trim($skuCode);
        $validator = (new UpdateSkuValidator($input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $sku = $validator->getSku();
        if($sku->name != trim($input['name'])){
            $sku->name = trim($input['name']);
            $sku->save();
        }

        return $this->response()->success($sku);
    }
}
