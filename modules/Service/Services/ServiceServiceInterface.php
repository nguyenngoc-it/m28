<?php

namespace Modules\Service\Services;

use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Gobiz\ModelQuery\ModelQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Location\Models\Location;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceCombo;
use Modules\Service\Models\ServicePack;
use Modules\Service\Models\ServicePrice;
use Modules\User\Models\User;

interface ServiceServiceInterface
{
    /**
     * @param array $input
     * @param Location $country
     * @param User $user
     * @return Service
     */
    public function create(array $input, Location $country, User $user): Service;

    /**
     * @param array $input
     * @param Service $service
     * @param User $user
     * @return Service
     */
    public function update(array $input, Service $service, User $user): Service;

    /**
     * @param array $input
     * @param Service $service
     * @param User $user
     * @return ServicePrice
     * @return ServicePrice
     */
    public function createServicePrice(array $input, Service $service, User $user): ServicePrice;

    /**
     * @param array $input
     * @param ServicePrice $servicePrice
     * @param User $user
     * @return ServicePrice
     */
    public function updateServicePrice(array $input, ServicePrice $servicePrice, User $user): ServicePrice;

     /**
     * @param array $input
     * @param User $user
     * @return void
     */
    public function updateServicePriceAllMerchants(array $input, User $user);

    /**
     * @param Collection $products
     * @param User $user
     * @return void
     */
    public function updateServicePriceProduct(Collection $products, User $user);

    /**
     * @param array $input
     * @param Service $service
     * @param User $user
     * @return Service
     */
    public function isRequired(array $input, Service $service, User $user): Service;


    /**
     * @param Service $service
     * @param string $status
     * @param bool $confirm
     * @param User $user
     * @return Service
     */
    public function changeStatus(Service $service, string $status, bool $confirm, User $user): Service;


    /**
     * Set dịch vụ bắt buộc chọn cho sản phẩm
     *
     * @param Service $service
     * @param Collection|null $products
     * @param User $user
     * @return void
     */
    public function setRequiredForProducts(Service $service, User $user, Collection $products = null);

    /**
     * Ước tính chi phí dịch vụ theo thông tin sản phẩm
     *
     * @param array $input
     * @param Collection $services
     * @return array
     */
    public function estimateFee(array $input, Collection $services): array;

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
    public function exportStorageFeeDaily(array $inputs, User $user): string;

    /**
     * @param array $inputs
     * @param Location $country
     * @param User $user
     * @return ServicePack
     */
    public function createServicePack(array $inputs, Location $country, User $user): ServicePack;

    /**
     * @param array $filter
     * @return ModelQuery
     */
    public function queryServicePack(array $filter): ModelQuery;

    /**
     * @param array $filter
     * @return Collection|LengthAwarePaginator
     */
    public function listingServicePack(array $filter);

    /**
     * @param ServicePack $servicePack
     * @param array $inputs
     * @param User $user
     * @return ServicePack
     */
    public function updateServicePack(ServicePack $servicePack, array $inputs, User $user): ServicePack;

    /**
     * @param ServicePack $servicePack
     * @param array $sellerIds
     * @param User $user
     * @return ServicePack
     */
    public function addSellerServicePack(ServicePack $servicePack, array $sellerIds, User $user): ServicePack;

    /**
     * @param ServicePack $servicePack
     * @param array $inputs
     * @param User $user
     * @return ServiceCombo
     */
    public function createServiceCombo(ServicePack $servicePack, array $inputs, User $user): ServiceCombo;
}
