<?php

namespace Modules\ShopBase\Controllers;

use App\Base\Controller;
use Modules\Service;
use Modules\ShopBase\Jobs\CreateOrderJob;
use Modules\ShopBase\Models\ShopBase;
use Modules\ShopBase\Validators\VerifyWebHookValidator;

class ShopBaseController extends Controller
{
    /**
     * @param $merchantId
     * @return \Illuminate\Http\JsonResponse
     */
    public function createOrder($merchantId)
    {
        $input     = file_get_contents('php://input');
        $shopBase  = ShopBase::create(['merchant_id' => $merchantId, 'data' => $input]);

        $validator = (new VerifyWebHookValidator($merchantId, $input));
        if ($validator->fails()) {
            $shopBase->update(['errors' => json_encode(['validate' => $validator->errors()])]);

            return $this->response()->success();
        }

        dispatch(new CreateOrderJob($shopBase->id));

        return $this->response()->success();
    }
}
