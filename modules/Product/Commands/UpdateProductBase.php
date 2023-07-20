<?php

namespace Modules\Product\Commands;

use Gobiz\Support\Helper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Modules\Category\Models\Category;
use Modules\Merchant\Models\Merchant;
use Modules\Product\Models\Product;
use Modules\Product\Models\Sku;
use Modules\Product\Models\Unit;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServicePrice;
use Modules\User\Models\User;

abstract class UpdateProductBase
{
    /** @var Merchant|null */
    protected $merchant;
    /** @var Product */
    protected $product;
    protected $input;
    /** @var User */
    protected $user;
    protected $payloadLogs = [];
    protected $autoPrice;

    /**
     * UpdateProductBase constructor.
     * @param Product $product
     * @param array $input
     * @param User $user
     * @param Merchant|null $merchant
     */
    public function __construct(Product $product, array $input, User $user, Merchant $merchant = null)
    {
        $this->merchant  = $merchant ?: $user->merchant;
        $this->product   = $product;
        $this->input     = array_map(function ($x) {
            if (is_string($x)) {
                return trim($x);
            }
            return $x;
        }, $input);
        $this->user      = $user;
        $this->autoPrice = Arr::get($this->input, 'auto_price', false);
    }

    /**
     * @param bool $updateBaseForSku
     */
    protected function updateBase($updateBaseForSku = true)
    {
        $name           = Arr::get($this->input, 'name');
        $code           = Arr::get($this->input, 'code');
        $weight         = Arr::get($this->input, 'weight');
        $height         = Arr::get($this->input, 'height');
        $width          = Arr::get($this->input, 'width');
        $length         = Arr::get($this->input, 'length');
        $categoryId     = Arr::get($this->input, 'category_id');
        $changeCode     = false;
        $changeCategory = false;
        $changeSize     = false;
        $changeUnit     = false;
        foreach ([
                     'name' => $name,
                     'code' => $code,
                     'category_id' => $categoryId,
                     'weight' => $weight,
                     'height' => $height,
                     'width' => $width,
                     'length' => $length
                 ] as $field => $value) {
            if (!is_null($value)) {

                if ($this->product->{$field} !== $value) {

                    if (in_array($field, ['weight', 'height', 'width', 'length'])) {
                        $changeSize = true;
                    }
                    if ($field == 'code') {
                        $changeCode = true;
                    }

                    $old = $this->product->{$field};
                    $new = $value;
                    if ($field == 'category_id') {
                        $changeCategory = true;
                        $categoryOld    = Category::find($this->product->category_id);
                        $categoryNew    = Category::find($categoryId);
                        $old            = ($categoryOld instanceof Category) ? $categoryOld->name : '';
                        $new            = ($categoryNew instanceof Category) ? $categoryNew->name : '';
                    }

                    if ($field == 'unit_id') {
                        $changeUnit = true;
                        $unitOld    = Unit::find($this->product->unit_id);
                        $unitNew    = Unit::find($value);
                        $old        = ($unitOld instanceof Unit) ? $unitOld->name : '';
                        $new        = ($unitNew instanceof Unit) ? $unitNew->name : '';
                    }

                    $this->payloadLogs[$field]['old'] = $old;
                    $this->payloadLogs[$field]['new'] = $new;
                    $this->product->{$field}          = $value;
                }
            }
        }

        $this->product->save();

        if ($changeCategory) {
            $this->product->skus()->update([
                'category_id' => $this->product->category_id,
            ]);
        }
        if ($changeUnit) {
            $this->product->skus()->update([
                'unit_id' => $this->product->unit_id,
            ]);
        }

        if ($changeCode && $updateBaseForSku) {
            $this->updateBaseForSku();
        }

        if ($changeSize) {
            $this->updateWeightSizeForSku();
        }
    }

    /**
     * @param $removedUrls
     */
    protected function removeSkuImages($removedUrls)
    {
        $skus = $this->product->skus;
        /** @var Sku $sku */
        foreach ($skus as $sku) {
            if (!empty($sku->images)) {
                $images = [];
                foreach ($sku->images as $image) {
                    if (!in_array($image, $removedUrls)) {
                        $images[] = $image;
                    }
                }

                if (count($images) != count($sku->images)) {
                    $sku->images = $images;
                    $sku->save();
                }
            }
        }
    }

    /**
     * Cập nhật hình ảnh sản phẩm
     */
    protected function updateImages()
    {
        $images       = $this->product->images;
        $uploadUrls   = $removedUrls = [];
        $files        = Arr::get($this->input, 'files');
        $removedFiles = Arr::get($this->input, 'removed_files');

        if ($removedFiles) {
            foreach ($removedFiles as $removedFile) {
                $removedUrls[] = $removedFile;
                $path          = 'products/' . $this->product->code . '/' . basename($removedFile);
                if ($this->product->tenant->storage()->exists($path)) {
                    $this->product->tenant->storage()->delete($path);
                }
            }
            if ($removedUrls) {
                $images                                 = array_diff((array)$images, $removedUrls);
                $this->payloadLogs['images']['removed'] = $removedUrls;

                $this->removeSkuImages($removedUrls);
            }
        }
        if ($files) {
            /** @var UploadedFile $file */
            foreach ($files as $file) {
                $nameFile = Helper::quickRandom(10);
                $uploaded = $this->product->tenant->storage()->put('products/' . $this->product->code . '/' . $nameFile . '.' . $file->extension(), $file->openFile(), 'public');
                if ($uploaded) {
                    $uploadedUrl  = $this->product->tenant->storage()->url('products/' . $this->product->code . '/' . $nameFile . '.' . $file->extension());
                    $uploadUrls[] = $uploadedUrl;
                    unlink($file->getRealPath());
                }
            }
            if ($uploadUrls) {
                $images                               = array_merge((array)$images, $uploadUrls);
                $this->payloadLogs['images']['added'] = $uploadUrls;
            }
        }

        $productImages = [];
        if (!empty($images) && is_array($images)) {
            foreach ($images as $image) {
                $productImages[] = $image;
            }
        }

        $this->product->images = $productImages;
        $this->product->save();
    }

    /**
     * Cập nhật dịch vụ sản phẩm theo đơn giá mặc định nếu seller không có gói dịch vụ
     * Nếu có gói dịch vụ thì đơn giá theo gói
     */
    protected function updateServices()
    {
        $servicesIds = Arr::get($this->input, 'services');
        if (!is_null($servicesIds)) {
            $syncServices      = [];
            $services          = [];
            $idProductServices = $this->product->services->pluck('id')->all();
            foreach ($servicesIds as $serviceId) {
                $service = Service::find($serviceId);
                if (
                    $service instanceof Service &&
                    $service->status == Service::STATUS_ACTIVE
                ) {
                    if ($this->merchant->servicePack) {
                        $servicePackPrice    = $this->merchant->servicePack->servicePackPrices->where('service_id', $serviceId)->first();
                        $servicePriceDefault = $servicePackPrice ? $servicePackPrice->servicePrice : null;
                    } else {
                        $servicePriceDefault = $service->servicePriceDefault();
                    }

                    if (in_array($service->id, $idProductServices)) {
                        $servicePriceExist                            = $this->product->servicePriceOfService($service);
                        $syncServices[$serviceId]['tenant_id']        = $this->product->tenant_id;
                        $syncServices[$serviceId]['service_price_id'] = $servicePriceExist->id;
                        $services[$serviceId]                         = [
                            'service' => $service->attributesToArray(),
                            'service_price' => $servicePriceExist->attributesToArray()
                        ];
                    } else {
                        $syncServices[$serviceId]['tenant_id']        = $this->product->tenant_id;
                        $syncServices[$serviceId]['service_price_id'] = $servicePriceDefault->id;
                        $services[$serviceId]                         = [
                            'service' => $service->attributesToArray(),
                            'service_price' => $servicePriceDefault->attributesToArray()
                        ];
                    }
                }
            }

            $productServices   = $this->product->services;
            $productServiceIds = [];
            /** @var Service $service */
            foreach ($productServices as $service) {
                $productServiceIds[] = $service->id;
                if (!in_array($service->id, $servicesIds)) {
                    $this->payloadLogs['services']['removed'][] = [
                        'service' => (isset($services[$service->id])) ? $services[$service->id]['service'] : $service->attributesToArray(),
                        'service_price' => (isset($services[$service->id])) ? $services[$service->id]['service_price'] : $service->servicePriceDefault()->attributesToArray(),
                    ];
                }
            }

            foreach ($services as $serviceId => $data) {
                if (!in_array($serviceId, $productServiceIds)) {
                    $this->payloadLogs['services']['added'][] = $data;
                }
            }

            $this->product->services()->sync($syncServices);
        }
    }

    /**
     * Cập nhật dịch vụ sản phẩm theo đơn giá chỉ định
     */
    protected function updateServicePrices()
    {
        $servicePriceIds = Arr::get($this->input, 'service_prices');
        if (!is_null($servicePriceIds)) {
            $syncServices  = [];
            $servicePrices = [];
            foreach ($servicePriceIds as $servicePriceId) {
                $servicePrice = ServicePrice::find($servicePriceId);
                if ($servicePrice instanceof ServicePrice) {
                    $service                                     = $servicePrice->service;
                    $syncServices[$servicePriceId]['tenant_id']  = $this->product->tenant_id;
                    $syncServices[$servicePriceId]['service_id'] = $service->id;

                    $servicePrices[$servicePriceId] = [
                        'service' => $service->attributesToArray(),
                        'service_price' => $servicePrice->attributesToArray()
                    ];
                }
            }

            $productServicePrices   = $this->product->servicePrices;
            $productServicePriceIds = [];
            /** @var ServicePrice $servicePrice */
            foreach ($productServicePrices as $servicePrice) {
                $productServicePriceIds[] = $servicePrice->id;
                if (!in_array($servicePrice->id, $servicePriceIds)) {
                    $this->payloadLogs['service_prices']['removed'][] = [
                        'service' => (isset($servicePrices[$servicePrice->id])) ? $servicePrices[$servicePrice->id]['service'] : $servicePrice->service->attributesToArray(),
                        'service_price' => (isset($servicePrices[$servicePrice->id])) ? $servicePrices[$servicePrice->id]['service_price'] : $servicePrice->attributesToArray(),
                    ];
                }
            }

            foreach ($servicePrices as $servicePriceId => $data) {
                if (!in_array($servicePriceId, $productServicePriceIds)) {
                    $this->payloadLogs['service_prices']['added'][] = $data;
                }
            }

            // empty relation completely
            $this->product->servicePrices()->sync([]);
            // Resync
            $changes  = $this->product->servicePrices()->sync($syncServices);
            $attached = data_get($changes, 'attached', []);

            // Nếu attach thành công mới ghi log
            if (!count($attached)) {
                $this->payloadLogs['service_prices'] = [];
            }
        }
    }

    /**
     * Cập nhật cân nặng và kích thước cho SKU của Sản phẩm
     */
    private function updateWeightSizeForSku()
    {
        $this->product->skus()->update([
            'weight' => $this->product->weight,
            'height' => $this->product->height,
            'width' => $this->product->width,
            'length' => $this->product->length,
        ]);
    }

    private function updateBaseForSku()
    {
        $this->product->skus()->update([
            'name' => $this->product->name,
            'code' => $this->product->code
        ]);
    }
}
