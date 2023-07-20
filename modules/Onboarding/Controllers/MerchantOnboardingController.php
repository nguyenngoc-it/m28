<?php

namespace Modules\Onboarding\Controllers;

use App\Base\Controller;
use Carbon\Carbon;
use Modules\Location\Models\Location;
use Modules\Onboarding\Commands\MerchantOnboardingStats;
use Modules\Onboarding\Validators\OrderStatsValidator;

class MerchantOnboardingController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $merchant = $this->getAuthUser()->merchant;
        return $this->response()->success([
            'product_total' => $merchant->productMerchants()->count(),
            'purchasing_package_total' => $merchant->purchasingPackages()->count(),
            'order_total' => $merchant->orders()->count(),
        ]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        $filter = $this->request()->only(['created_at']);
        if(empty($filter['created_at'])) {
            $filter['created_at'] = [
                'from' => (new Carbon())->subDays(7)->toDateTimeString(),
                'to' => (new Carbon())->toDateTimeString(),
            ];
        }

        $validator = new OrderStatsValidator($filter);
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $filter['tenant_id']   = $this->user->tenant_id;
        $filter['merchant_id'] = $this->user->merchant->id;

        $stats = (new MerchantOnboardingStats($filter, $this->user))->handle();
        $stats['currency'] = $this->user->merchant->getCurrency();

        return $this->response()->success($stats);
    }
}
