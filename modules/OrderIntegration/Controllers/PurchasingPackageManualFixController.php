<?php

namespace Modules\OrderIntegration\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Modules\OrderIntegration\Validators\CancelPurchasePackageValidator;
use Modules\PurchasingPackage\Models\PurchasingPackage;

class PurchasingPackageManualFixController extends Controller
{
    /**
     * @param $packageCode
     * @return JsonResponse
     */
    public function cancel($packageCode)
    {
        $input = $this->request()->only(['tenant_code','merchant_code']);
        $input['package_code'] = trim($packageCode);
        $validator = (new CancelPurchasePackageValidator($input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $purchasingPackage = $validator->getPurchasingPackage();
        if($purchasingPackage->status != PurchasingPackage::STATUS_CANCELED){
            $purchasingPackage->changeStatus(PurchasingPackage::STATUS_CANCELED, $this->user);
        }

        return $this->response()->success($purchasingPackage);
    }
}
