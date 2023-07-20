<?php

namespace Modules\PurchasingManager\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Modules\PurchasingManager\Models\PurchasingService;

class PurchasingServiceController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function suggest()
    {
        return $this->response()->success(
            ['purchasing_services' => PurchasingService::query()->where([
                'active' => true,
                'tenant_id' => $this->user->tenant_id
            ])->get()]
        );
    }

}
