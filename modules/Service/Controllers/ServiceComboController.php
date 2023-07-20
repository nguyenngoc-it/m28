<?php

namespace Modules\Service\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Merchant\Models\Merchant;
use Modules\Service;
use Modules\Service\Validators\CreateServiceComboValidator;

class ServiceComboController extends Controller
{
    /**
     * @param Service\Models\ServiceCombo $serviceCombo
     * @return array
     */
    protected function transformDetailServiceCombo(Service\Models\ServiceCombo $serviceCombo): array
    {
        return [
            'service_combo' => $serviceCombo,
            'service_pack_prices' => $serviceCombo->servicePackPrices->sortBy('created_at', SORT_REGULAR, true)->values()->map(function (Service\Models\ServicePackPrice $servicePackPrice) {
                return [
                    'service_pack_price' => $servicePackPrice,
                    'service' => $servicePackPrice->service->only(['id', 'type', 'code', 'name']),
                    'service_price' => $servicePackPrice->servicePrice->only(['id', 'label', 'price', 'yield_price', 'deduct', 'note'])
                ];
            })
        ];
    }

    /**
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        $inputs    = $this->request()->only([
            'service_pack_id',
            'code',
            'name',
            'note',
            'using_days',
            'using_skus',
            'suggest_price',
            'service_price_quotas'
        ]);
        $validator = (new CreateServiceComboValidator($inputs));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $serviceCombo = Service::service()->createServiceCombo($validator->getServicePack(), $inputs, $this->user);

        return $this->response()->success(
            $this->transformDetailServiceCombo($serviceCombo)
        );
    }

    /**
     * @param Service\Models\ServiceCombo $serviceCombo
     * @return JsonResponse
     */
    public function detail(Service\Models\ServiceCombo $serviceCombo): JsonResponse
    {
        return $this->response()->success(
            array_merge(
                $this->transformDetailServiceCombo($serviceCombo),
                ['country' => array_merge($serviceCombo->country->only('label'), ['currency' => $serviceCombo->country->currency])],
                ['merchants' => $serviceCombo->merchants->map(function (Merchant $merchant) {
                    return ['merchant' => $merchant->only(['id', 'code', 'username', 'name', 'service_pack_added_at'])];
                })]
            )
        );
    }
}
