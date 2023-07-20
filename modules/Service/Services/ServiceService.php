<?php

namespace Modules\Service\Services;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Carbon\Carbon;
use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Location\Models\Location;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductServicePrice;
use Modules\Service\Commands\EstimateFee;
use Modules\Service\Commands\ChangeServiceStatusCommand;
use Modules\Service\Commands\ExportStorageFee;
use Modules\Service\Commands\ServicePackQuery;
use Modules\Service\Commands\UpdateServicePriceAllMerchantsCommand;
use Modules\Service\Commands\UpdateServicePriceProductCommand;
use Modules\Service\Events\ServiceComboCreated;
use Modules\Service\Events\ServicePackCreated;
use Modules\Service\Events\ServicePackPriceAdded;
use Modules\Service\Events\ServicePackPriceRemoved;
use Modules\Service\Events\ServicePackSellerAdded;
use Modules\Service\Events\ServicePackSellerRemoved;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceCombo;
use Modules\Service\Models\ServiceComboPrice;
use Modules\Service\Models\ServicePack;
use Modules\Service\Models\ServicePackPrice;
use Modules\Service\Models\ServicePrice;
use Modules\User\Models\User;

class ServiceService implements ServiceServiceInterface
{

    /**
     * @param array $input
     * @param Location $country
     * @param User $user
     * @return Service
     */
    public function create(array $input, Location $country, User $user): Service
    {
        $newService = Service::create(array_merge($input, [
            'tenant_id' => $user->tenant_id,
            'country_id' => $country->id
        ]));
        $newService->logActivity(ServiceEvent::CREATE, $user);
        return $newService;
    }

    /**
     * @param array $input
     * @param Service $service
     * @param User $user
     * @return Service
     */
    public function update(array $input, Service $service, User $user): Service
    {
        $service->update($input);
        $service->logActivity(ServiceEvent::UPDATE, $user, $input);
        return $service->refresh();
    }

    /**
     * @param array $input
     * @param Service $service
     * @param User $user
     * @return ServicePrice
     */
    public function createServicePrice(array $input, Service $service, User $user): ServicePrice
    {
        $newServicePrice = ServicePrice::create(array_merge($input, [
            'tenant_id' => $user->tenant_id,
            'country_id' => $service->country_id,
            'service_code' => $service->code
        ]));
        $newServicePrice->update(['service_price_code' => $service->code . '-' . $newServicePrice->id]);

        $defaultServicePrice = $service->servicePrices->where('is_default', true)->first();
        if (empty($defaultServicePrice)) {
            $newServicePrice->is_default = true;
            $newServicePrice->save();
        }

        $newServicePrice->logActivity(ServiceEvent::CREATE_SERVICE_PRICE, $user);
        return $newServicePrice;
    }

    /**
     * @param array $input
     * @param ServicePrice $servicePrice
     * @param User $user
     * @return ServicePrice
     */
    public function updateServicePrice(array $input, ServicePrice $servicePrice, User $user): ServicePrice
    {
        if (!$servicePrice->service_price_code) {
            $input = array_merge($input, [
                'service_price_code' => $servicePrice->service_code . '-' . $servicePrice->id
            ]);
        }
        $servicePrice->update($input);
        $servicePrice->logActivity(ServiceEvent::UPDATE_SERVICE_PRICE, $user, $input);
        return $servicePrice->refresh();
    }

    /**
     * @param array $input
     * @param User $user
     * @return void
     */
    public function updateServicePriceAllMerchants(array $input, User $user)
    {
        return (new UpdateServicePriceAllMerchantsCommand($input, $user))->handle();
    }


    /**
     * @param Collection $products
     * @param User $user
     * @return void
     */
    public function updateServicePriceProduct(Collection $products, User $user)
    {
        return (new UpdateServicePriceProductCommand($products, $user))->handle();
    }

    /**
     * @param array $input
     * @param Service $service
     * @param User $user
     * @return Service
     */
    public function isRequired(array $input, Service $service, User $user): Service
    {
        $service->is_required = Arr::get($input, 'is_required', false);
        $service->save();
        if ($service->is_required) {
            \Modules\Service::service()->setRequiredForProducts($service, $user);
        }
        $service->logActivity(ServiceEvent::CREATE_SERVICE_PRICE, $user);

        return $service->refresh();
    }

    /**
     * @param Service $service
     * @param Service $status
     * @param bool $confirm
     * @param User $user
     * @return Service
     */
    public function changeStatus(Service $service, $status, bool $confirm, User $user): Service
    {
        return (new ChangeServiceStatusCommand($service, $status, $confirm, $user))->handle();
    }


    /**
     * Set dịch vụ mặc định cho sản phẩm
     *
     * @param Service $service
     * @param Collection|null $products
     * @param User $user
     * @return void
     */
    public function setRequiredForProducts(Service $service, User $user, Collection $products = null)
    {
        if (empty($products)) {
            /**
             * Set cho tất cả sp của thị trường, sử dụng trong th chọn dịch vụ mặc địch
             */
            $merchants = Merchant::query()->where(
                [
                    'tenant_id' => $service->tenant_id,
                    'location_id' => $service->country_id
                ]
            )->get();
            if ($merchants->count()) {
                $products = Product::query()->where('tenant_id', $service->tenant_id)
                    ->whereIn('merchant_id', $merchants->pluck('id')->all())
                    ->get();
            }
        }

        /** @var Product $product */
        foreach ($products as $product) {
            /** Nếu sản phẩm mà có 1 sku thì tự động cập nhật theo mức giá tự động */
            if ($product->skus->count() == 1) {
                $servicePrice = \Modules\Service::product()->autoGetSkuServicePrice($product->skus->first(), $service, $user);
                if (empty($servicePrice)) {
                    $servicePrice = $service->servicePriceDefault();
                }
            } else {
                /**
                 * Ngược lại thì dùng mức giá mặc định
                 */
                $servicePrice = $service->servicePriceDefault();
            }
            if ($servicePrice) {
                $product->productServicePrices()->where('service_id', $service->id)->delete();
                ProductServicePrice::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'tenant_id' => $product->tenant_id,
                        'service_price_id' => $servicePrice->id,

                    ],
                    [
                        'service_id' => $service->id,
                    ]
                );
            }
        }
    }

    /**
     * Ước tính chi phí dịch vụ theo thông tin sản phẩm
     * 'height', 'width', 'length', 'service_ids', 'quantity'
     *
     * @param array $input
     * @param Collection $services
     * @return array
     */
    public function estimateFee(array $input, Collection $services): array
    {
        return (new EstimateFee($input, $services))->handle();
    }

    /**
     * xuất danh sách phí lưu kho sku theo ngày
     *
     * @param array $inputs
     * @param User $user
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function exportStorageFeeDaily(array $inputs, User $user): string
    {
        return (new ExportStorageFee($inputs, $user))->handle();
    }

    /**
     * @param array $inputs
     * @param Location $country
     * @param User $user
     * @return ServicePack
     */
    public function createServicePack(array $inputs, Location $country, User $user): ServicePack
    {
        return DB::transaction(function () use ($inputs, $country, $user) {
            $servicePack     = ServicePack::create([
                'tenant_id' => $user->tenant_id,
                'country_id' => $country->id,
                'code' => Arr::get($inputs, 'code'),
                'name' => Arr::get($inputs, 'name'),
                'note' => Arr::get($inputs, 'note'),
            ]);
            $servicePriceIds = Arr::get($inputs, 'service_price_ids');
            $servicePrices   = ServicePrice::query()->whereIn('id', $servicePriceIds)->with(['serviceRelate'])->get();
            /** @var ServicePrice $servicePrice */
            foreach ($servicePrices as $servicePrice) {
                /** @var Service $service */
                $service = $servicePrice->serviceRelate()->where('tenant_id', $servicePrice->tenant_id)->first();
                $servicePack->servicePackPrices()->create([
                    'service_price_id' => $servicePrice->id,
                    'type' => $service->type,
                    'service_id' => $service->id
                ]);
            }
            (new ServicePackCreated($servicePack, $user))->queue();

            return $servicePack;
        });
    }

    /**
     * @param array $filter
     * @return ModelQuery
     */
    public function queryServicePack(array $filter): ModelQuery
    {
        return (new ServicePackQuery())->query($filter);
    }

    /**
     * @param array $filter
     * @return Collection|LengthAwarePaginator
     */
    public function listingServicePack(array $filter)
    {
        $page     = Arr::pull($filter, 'page', config('paginate.page'));
        $perPage  = Arr::pull($filter, 'per_page', config('paginate.per_page'));
        $paginate = Arr::pull($filter, 'paginate', true);
        $sortBy   = Arr::pull($filter, 'sort_by', 'id');
        $sort     = Arr::pull($filter, 'sort', 'desc');

        $query = \Modules\Service::service()->queryServicePack($filter)->getQuery();
        $query->orderBy('service_packs' . '.' . $sortBy, $sort);
        $query->with(['country', 'servicePackPrices']);

        if (!$paginate) {
            return $query->get();
        }

        return $query->paginate($perPage, ['service_packs.*'], 'page', $page);
    }

    /**
     * @param ServicePack $servicePack
     * @param array $inputs
     * @param User $user
     * @return ServicePack
     */
    public function updateServicePack(ServicePack $servicePack, array $inputs, User $user): ServicePack
    {
        return DB::transaction(function () use ($inputs, $servicePack, $user) {
            $note = Arr::get($inputs, 'note');
            if (!is_null($note)) {
                $servicePack->note = $servicePack;
                $servicePack->save();
            }

            $servicePriceIds        = Arr::get($inputs, 'service_price_ids');
            $prevServicePriceIds    = $servicePack->servicePackPrices->pluck('service_price_id')->all();
            $addedServicePriceIds   = array_diff($servicePriceIds, $prevServicePriceIds);
            $removedServicePriceIds = array_diff($prevServicePriceIds, $servicePriceIds);
            if ($removedServicePriceIds) {
                $servicePack->servicePackPrices()->whereIn('service_price_id', $removedServicePriceIds)->delete();
                (new ServicePackPriceRemoved($servicePack->id, $removedServicePriceIds, $user))->queue();
            }
            if ($addedServicePriceIds) {
                $addedServicePrices = ServicePrice::query()->whereIn('id', $addedServicePriceIds)->with('serviceRelate')->get();
                /** @var ServicePrice $addedServicePrice */
                foreach ($addedServicePrices as $addedServicePrice) {
                    /** @var Service $service */
                    $service = $addedServicePrice->serviceRelate()->where('tenant_id', $addedServicePrice->tenant_id)->first();
                    $servicePack->servicePackPrices()->create([
                        'service_price_id' => $addedServicePrice->id,
                        'type' => $service->type,
                        'service_id' => $service->id
                    ]);
                }
                (new ServicePackPriceAdded($servicePack->id, $addedServicePriceIds, $user))->queue();
            }

            return $servicePack;
        });
    }

    /**
     * @param ServicePack $servicePack
     * @param array $sellerIds
     * @param User $user
     * @return ServicePack
     */
    public function addSellerServicePack(ServicePack $servicePack, array $sellerIds, User $user): ServicePack
    {
        return DB::transaction(function () use ($servicePack, $sellerIds, $user) {
            $merchants        = Merchant::query()->whereIn('id', $sellerIds)->get();
            $addedMerchantIds = [];
            /** @var Merchant $merchant */
            foreach ($merchants as $merchant) {
                if ($merchant->service_pack_id != $servicePack->id) {
                    $addedMerchantIds[] = $merchant->id;
                    if ($merchant->servicePack) {
                        (new ServicePackSellerRemoved($merchant->servicePack->id, $merchant->id, $user))->queue();
                    }
                    $merchant->service_pack_id       = $servicePack->id;
                    $merchant->service_pack_added_at = Carbon::now();
                    $merchant->save();
                }
            }
            if ($addedMerchantIds) {
                (new ServicePackSellerAdded($servicePack->id, $sellerIds, $user))->queue();
            }

            return $servicePack;
        });
    }

    /**
     * @param ServicePack $servicePack
     * @param array $inputs
     * @param User $user
     * @return ServiceCombo
     */
    public function createServiceCombo(ServicePack $servicePack, array $inputs, User $user): ServiceCombo
    {
        return DB::transaction(function () use ($servicePack, $inputs, $user) {
            $serviceCombo = ServiceCombo::create([
                'service_pack_id' => $servicePack->id,
                'tenant_id' => $user->tenant_id,
                'country_id' => $servicePack->country_id,
                'code' => Arr::get($inputs, 'code'),
                'name' => Arr::get($inputs, 'name'),
                'note' => Arr::get($inputs, 'note'),
                'using_days' => Arr::get($inputs, 'using_days', 30),
                'using_skus' => Arr::get($inputs, 'using_skus', 0),
                'suggest_price' => Arr::get($inputs, 'suggest_price', 0)
            ]);

            $servicePriceQuotas = Arr::get($inputs, 'service_price_quotas');
            $servicePriceQuotas = collect($servicePriceQuotas)->pluck('quota', 'service_price_id')->all();
            /** @var ServicePackPrice $servicePackPrice */
            foreach ($servicePack->servicePackPrices as $servicePackPrice) {
                ServiceComboPrice::create([
                    'service_combo_id' => $serviceCombo->id,
                    'service_price_id' => $servicePackPrice->service_price_id,
                    'type' => $servicePackPrice->type,
                    'service_id' => $servicePackPrice->service_id,
                    'quota' => $servicePriceQuotas[$servicePackPrice->service_price_id]
                ]);
            }

            (new ServiceComboCreated($serviceCombo, $user))->queue();

            return $serviceCombo;
        });
    }
}
