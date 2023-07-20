<?php /** @noinspection PhpUnusedParameterInspection */

namespace Modules\Service\Controllers;

use App\Base\Controller;
use Illuminate\Support\Arr;
use Modules\Location\Models\Location;
use Modules\Merchant\Models\Merchant;
use Modules\Service\Models\Service;
use Illuminate\Http\JsonResponse;
use Modules\Service\Jobs\UpdateServicePriceAllMerchantsJob;
use Modules\Service\Models\ServicePrice;
use Modules\Service\Services\ServiceEvent;
use Modules\Service\Transformers\ServiceListItemTransformer;
use Modules\Service\Validators\ChangeServiceStatusValidator;
use Modules\Service\Validators\CreateServicePriceValidator;
use Modules\Service\Validators\CreateServiceValidator;
use Modules\Service\Validators\IsDefaultServicePriceValidator;
use Modules\Service\Validators\UpdatingServicePriceValidator;
use Modules\Service\Validators\UpdatingServicePriceAllMerchantsValidator;
use Modules\Service\Validators\UpdatingServiceValidator;
use Modules\Service\Validators\IsRequiredServicePriceValidator;

class ServiceController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $input = $this->request()->only([
            'type', 'country_id', 'hidden_init_service', 'status', 'merchant_id'
        ]);
        $query = $this->user->tenant->services();
        if ($type = Arr::get($input, 'type')) {
            $query->where('services.type', strtoupper($type));
        }
        $currency = null;
        if ($countryId = Arr::get($input, 'country_id')) {
            $query->where('services.country_id', $countryId);

            /** @var Location $country */
            $country  = Location::find($countryId);
            $currency = ($country) ? $country->currency : null;
        }

        if ($status = Arr::get($input, 'status')) {
            $query->where('services.status', $status);
        }
        $serviceIds = [];
        if ($merchantId = Arr::get($input, 'merchant_id')) {
            $merchant = Merchant::find($merchantId);
            if(empty($currency)) {
                $currency = $merchant->getCurrency();
            }
            if ($merchant->servicePack) {
                $servicePackPrices = $merchant->servicePack->servicePackPrices;
                foreach ($servicePackPrices as $servicePackPrice) {
                    $servicePriceIds[] = $servicePackPrice->service_price_id;
                    $serviceIds[]      = $servicePackPrice->service_id;
                }
                $query->whereIn('services.id', $serviceIds);
            }
        }
        $hidden_init_service = Arr::get($input, 'hidden_init_service', false);

        $services = $query->with(['servicePrices'])->orderByDesc('created_at')->get();
        return $this->response()->success([
            'services' => $services->map(function (Service $service) use ($hidden_init_service) {
                return (new ServiceListItemTransformer())->transform($service, $hidden_init_service);
            })->filter()->values(),
            'currency' => $currency
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        $input     = $this->request()->only([
            'country_id',
            'type',
            'code',
            'name',
            'auto_price_by',
            'status'
        ]);
        $validator = (new CreateServiceValidator($input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $service = \Modules\Service::service()->create($input, $validator->getCountry(), $this->user);

        return $this->response()->success(compact('service'));
    }

    /**
     * @param Service $service
     * @return JsonResponse
     */
    public function update(Service $service): JsonResponse
    {
        $input     = $this->request()->only([
            'auto_price_by'
        ]);
        $validator = (new UpdatingServiceValidator($input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $service = \Modules\Service::service()->update($input, $service, $this->user);

        return $this->response()->success(compact('service'));
    }

    /**
     * @param Service $service
     * @return JsonResponse
     */
    public function createServicePrice(Service $service): JsonResponse
    {
        $input = $this->request()->only([
            'label',
            'price',
            'yield_price',
            'note',
            'height',
            'width',
            'length',
            'volume',
            'seller_codes',
            'seller_refs',
            'deduct'
        ]);
        if (isset($input['yield_price']) && $input['yield_price'] === '') {
            $input['yield_price'] = null;
        }
        $validator = (new CreateServicePriceValidator($service, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $servicePrice = \Modules\Service::service()->createServicePrice($input, $validator->getService(), $this->user);

        return $this->response()->success(['service_price' => $servicePrice]);
    }

    /**
     * @param Service $service
     * @param ServicePrice $servicePrice
     * @return JsonResponse
     */
    public function updateServicePrice(Service $service, ServicePrice $servicePrice): JsonResponse
    {
        $input     = $this->request()->only([
            'height', 'width', 'length', 'volume', 'seller_codes', 'seller_refs'
        ]);
        $validator = (new UpdatingServicePriceValidator($input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $servicePrice = \Modules\Service::service()->updateServicePrice($input, $servicePrice, $this->user);

        return $this->response()->success(['service_price' => $servicePrice]);
    }

    /**
     * Cập nhật lại toàn bộ dịch vụ cho sản phẩm của toàn bộ seller theo logic tự động chọn mức giá
     *
     * @param service $service
     * @return JsonResponse
     */
    public function updateServicePriceAllMerchants(Service $service): JsonResponse
    {
        $input     = $this->request()->only(['country_id']);
        $validator = (new UpdatingServicePriceAllMerchantsValidator($input));

        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        dispatch(new UpdateServicePriceAllMerchantsJob($input, $this->user));

        return $this->response()->success(['code' => 'SUCCESS', 'messages' => 'Yêu cầu đồng bộ dịch vụ cho sản phẩm thành công']);
    }

    /**
     * Đánh dấu dịch vụ bắt buộc
     *
     * @param Service $service
     * @return JsonResponse
     */
    public function isRequired(Service $service): JsonResponse
    {
        $input     = $this->request()->only([
            'is_required'
        ]);
        $validator = (new IsRequiredServicePriceValidator($service, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $service = \Modules\Service::service()->isRequired($input, $validator->getService(), $this->user);

        return $this->response()->success(['service' => $service]);
    }

    /**
     * Chuyển trạng thái dịch vụ
     * @param Service $service
     * @return JsonResponse
     */
    public function changeStatus(Service $service): JsonResponse
    {
        $input     = $this->request()->only([
            'status',
            'confirm'
        ]);
        $validator = (new ChangeServiceStatusValidator($service, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        $service = \Modules\Service::service()->changeStatus(
            $validator->getService(),
            trim($input['status']),
            $input['confirm'],
            $this->user
        );

        return $this->response()->success(['service' => $service]);
    }

    /**
     * @param Service $service
     * @return JsonResponse
     */
    public function listingServicePrice(Service $service): JsonResponse
    {
        return $this->response()->success([
            'service_prices' => $service->servicePrices
        ]);
    }

    /**
     * @param Service $service
     * @param ServicePrice $servicePrice
     * @return JsonResponse
     */
    public function isDefault(Service $service, ServicePrice $servicePrice): JsonResponse
    {
        $input     = $this->request()->only([
            'is_default'
        ]);
        $validator = (new IsDefaultServicePriceValidator($service, $servicePrice, $input));
        if ($validator->fails()) {
            return $this->response()->error($validator);
        }

        if ($validator->isDefault() && !$servicePrice->is_default) {
            $service->servicePrices()->update(['is_default' => false]);
            $servicePrice->update(['is_default' => true]);
            $servicePrice->save();
            $servicePrice->logActivity(ServiceEvent::DEFAULT_SERVICE_PRICE, $this->user);
        }

        return $this->response()->success(['service_price' => $servicePrice]);
    }
}
