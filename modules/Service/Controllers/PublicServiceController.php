<?php

namespace Modules\Service\Controllers;

use App\Base\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Modules\Location\Models\Location;
use Modules\Service;
use Modules\Service\Transformers\ServiceListItemTransformer;
use Modules\Service\Validators\EstimateFeeServiceValidator;

class PublicServiceController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function estimateFee()
    {
        $input = $this->request()->only([
            'height', 'width', 'length', 'service_ids', 'quantity', 'seller_ref'
        ]);

        $validator = (new EstimateFeeServiceValidator($input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $result = Service::service()->estimateFee($input, $validator->getServices());

        return $this->response()->success($result);
    }

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $input = $this->request()->only([
            'type', 'hidden_init_service', 'status'
        ]);
        /** @var Location $country */
        $country = Location::query()->where('code', 'vietnam')->first();
        $query   = Service\Models\Service::query()->where('country_id', $country->id);
        if ($type = Arr::get($input, 'type')) {
            $query->where('type', strtoupper($type));
        }
        if ($status = Arr::get($input, 'status')) {
            $query->where('status', $status);
        }
        $hidden_init_service = Arr::get($input, 'hidden_init_service', false);
        $services            = $query->with(['servicePrices'])->orderByDesc('created_at')->get();

        return $this->response()->success([
            'services' => $services->map(function (Service\Models\Service $service) use ($hidden_init_service) {
                return (new ServiceListItemTransformer())->transform($service, $hidden_init_service);
            })->filter()->values(),
            'currency' => $country->currency
        ]);
    }


}
