<?php

namespace Modules\Service\Controllers;

use App\Base\Controller;
use Gobiz\Activity\ActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Modules\Merchant\Models\Merchant;
use Modules\Service;
use Modules\Service\Validators\AddSellerServicePackValidator;
use Modules\Service\Validators\CreateServicePackValidator;
use Modules\Service\Validators\UpdateServicePackValidator;

class ServicePackController extends Controller
{
    /**
     * @param Service\Models\ServicePack $servicePack
     * @return array
     */
    protected function transformDetailServicePack(Service\Models\ServicePack $servicePack): array
    {
        return [
            'service_pack' => $servicePack,
            'service_pack_prices' => $servicePack->servicePackPrices->sortBy('created_at', SORT_REGULAR, true)->values()->map(function (Service\Models\ServicePackPrice $servicePackPrice) {
                return [
                    'service_pack_price' => $servicePackPrice,
                    'service' => $servicePackPrice->service->only(['id', 'type', 'code', 'name', 'is_required']),
                    'service_price' => $servicePackPrice->servicePrice->only(['id', 'label', 'price', 'yield_price', 'deduct', 'note'])
                ];
            })
        ];
    }

    /**
     * @param array $inputs
     * @return array
     */
    protected function getQueryFilter(array $inputs = []): array
    {
        $inputs = $inputs ?: [
            'country_id',
            'sort',
            'sortBy',
            'page',
            'per_page',
            'paginate'
        ];

        $filter              = $this->requests->only($inputs);
        $filter              = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $filter);
        $filter['tenant_id'] = $this->user->tenant_id;

        return $filter;
    }

    /**
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        $inputs    = $this->request()->only([
            'country_id',
            'code',
            'name',
            'note',
            'service_price_ids'
        ]);
        $validator = (new CreateServicePackValidator($inputs));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $servicePack = Service::service()->createServicePack($inputs, $validator->getCountry(), $this->user);

        return $this->response()->success(
            $this->transformDetailServicePack($servicePack)
        );
    }

    /**
     * @param Service\Models\ServicePack $servicePack
     * @return JsonResponse
     */
    public function detail(Service\Models\ServicePack $servicePack): JsonResponse
    {
        return $this->response()->success(
            array_merge(
                $this->transformDetailServicePack($servicePack),
                ['country' => array_merge($servicePack->country->only('label'), ['currency' => $servicePack->country->currency])],
                ['merchants' => $servicePack->merchants->map(function (Merchant $merchant) {
                    return ['merchant' => $merchant->only(['id', 'code', 'username', 'name', 'service_pack_added_at'])];
                })]
            )
        );
    }

    /**
     * @param Service\Models\ServicePack $servicePack
     * @return JsonResponse
     */
    public function sellerHistory(Service\Models\ServicePack $servicePack): JsonResponse
    {
        $logs = ActivityService::logger()->get('service_pack', $servicePack->id, ['action' => [
            Service\Services\ServiceEvent::SERVICE_PACK_ADD_SELLER,
            Service\Services\ServiceEvent::SERVICE_PACK_REMOVE_SELLER,
        ]]);
        return $this->response()->success([
            'seller_histories' => $logs
        ]);
    }

    /**
     * @param Service\Models\ServicePack $servicePack
     * @return JsonResponse
     */
    public function update(Service\Models\ServicePack $servicePack): JsonResponse
    {
        $inputs = $this->request()->only([
            'note',
            'service_price_ids'
        ]);

        $validator = (new UpdateServicePackValidator($inputs, $servicePack));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $servicePack = Service::service()->updateServicePack($servicePack, $inputs, $this->user);

        return $this->response()->success([
            $this->transformDetailServicePack($servicePack)
        ]);
    }

    /**
     * @param Service\Models\ServicePack $servicePack
     * @return JsonResponse
     */
    public function addSeller(Service\Models\ServicePack $servicePack): JsonResponse
    {
        $inputs    = $this->request()->only([
            'seller_ids'
        ]);
        $validator = (new AddSellerServicePackValidator($inputs, $servicePack));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }
        $servicePack = Service::service()->addSellerServicePack($servicePack, $inputs['seller_ids'], $this->user);
        return $this->response()->success([
            $this->transformDetailServicePack($servicePack)
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $filter   = $this->getQueryFilter();
        $listing  = Service::service()->listingServicePack($filter);
        $paginate = Arr::get($filter, 'paginate', true);
        if (!$paginate) {
            return $this->response()->success(
                [
                    'service_packs' => $listing->map(function (Service\Models\ServicePack $servicePack) {
                        return [
                            'service_pack' => $servicePack,
                            'service_combos' => $servicePack->serviceCombos
                        ];
                    })
                ]
            );
        } else {
            return $this->response()->success(
                [
                    'service_packs' => array_map(function (Service\Models\ServicePack $servicePack) {
                        return [
                            'service_pack' => $servicePack,
                            'service_combos' => $servicePack->serviceCombos
                        ];
                    }, $listing->items()),
                    'pagination' => $listing
                ]
            );
        }
    }
}
