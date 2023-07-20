<?php

namespace Modules\Product\Commands;

use Illuminate\Database\Eloquent\Collection;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuServicePrice;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServicePrice;
use Modules\User\Models\User;

class AutoUpdateSkuServicePrice
{
    /**
     * @var Sku|null
     */
    protected $sku = null;

    /**
     * @var Service|null
     */
    protected $service = null;

    /**
     * @var User|null
     */
    protected $creator = null;

    /** @var bool $autoSaveServicePrice */
    protected $autoSaveServicePrice = true;

    /**
     * UpdateSKU constructor.
     * @param Sku $sku
     * @param Service $service
     * @param User|null $creator
     * @param bool $autoSaveServicePrice
     */
    public function __construct(Sku $sku, Service $service, User $creator = null, bool $autoSaveServicePrice = true)
    {
        $this->sku                  = $sku;
        $this->creator              = $creator;
        $this->service              = $service;
        $this->autoSaveServicePrice = $autoSaveServicePrice;
    }

    /**
     * @return ServicePrice|null
     */
    public function handle(): ?ServicePrice
    {
        $servicePrices = $this->service->servicePrices;
        if ($this->service->auto_price_by == Service::SERVICE_AUTO_PRICE_BY_SIZE) {
            return $this->autoBySize($servicePrices);
        }
        if ($this->service->auto_price_by == Service::SERVICE_AUTO_PRICE_BY_VOLUME) {
            return $this->autoByVolume($servicePrices);
        }
        if ($this->service->auto_price_by == Service::SERVICE_AUTO_PRICE_BY_SELLER) {
            return $this->autoBySeller($servicePrices);
        }
        return null;
    }

    /**
     * Sku phải có 3 chiều <= 3 chiều của đơn giá,
     * TH có nhiều đơn giá thoả mãn thì sẽ lấy theo đơn giá nhỏ nhất
     *
     * @param Collection $servicePrices
     * @return ServicePrice|null
     */
    protected function autoBySize(Collection $servicePrices): ?ServicePrice
    {
        if ($this->sku->length * $this->sku->width * $this->sku->height == 0) {
            return null;
        }
        $allowServicePrices = collect([]);
        /** @var ServicePrice $servicePrice */
        foreach ($servicePrices as $servicePrice) {
            if ($servicePrice->length >= $this->sku->length && $servicePrice->width >= $this->sku->width
                && $servicePrice->height >= $this->sku->height) {
                $allowServicePrices->push($servicePrice);
            }
        }
        return $this->choiceAutoServicePrice($servicePrices, $allowServicePrices);
    }

    /**
     * Sku có thể tích <= thể tích đơn giá
     * TH có nhiều đơn giá thoả mãn thì sẽ lấy theo đơn giá nhỏ nhất
     * @param Collection $servicePrices
     *
     * @return ServicePrice|null
     */
    protected function autoByVolume(Collection $servicePrices): ?ServicePrice
    {
        $skuVolume = round($this->sku->length * $this->sku->width * $this->sku->height, 8);
        if (empty($skuVolume)) {
            return null;
        }
        $allowServicePrices = collect([]);
        /** @var ServicePrice $servicePrice */
        foreach ($servicePrices as $servicePrice) {
            if ($skuVolume <= $servicePrice->volume) {
                $allowServicePrices->push($servicePrice);
            }
        }
        return $this->choiceAutoServicePrice($servicePrices, $allowServicePrices);
    }

    /**
     * Sku của seller có mã hoặc mã giới thiệu thuộc đơn giá được add mã
     * TH có nhiều đơn giá thoả mãn thì lấy theo đơn giá nhỏ nhất
     * @param Collection $servicePrices
     *
     * @return ServicePrice|null
     */
    protected function autoBySeller(Collection $servicePrices): ?ServicePrice
    {
        $allowServicePrices = collect([]);
        $merchant           = $this->sku->product ? $this->sku->product->merchant : null;
        if (empty($merchant)) {
            if ($this->sku->seller_ref) {
                /** @var ServicePrice $servicePrice */
                foreach ($servicePrices as $servicePrice) {
                    if (in_array($this->sku->seller_ref, $servicePrice->seller_refs)) {
                        $allowServicePrices->push($servicePrice);
                    }
                }
            }
        } else {
            /** @var ServicePrice $servicePrice */
            foreach ($servicePrices as $servicePrice) {
                if (in_array($merchant->ref, $servicePrice->seller_refs)) {
                    $allowServicePrices->push($servicePrice);
                } else if (in_array($merchant->code, $servicePrice->seller_codes)) {
                    $allowServicePrices->push($servicePrice);
                }
            }
        }

        return $this->choiceAutoServicePrice($servicePrices, $allowServicePrices);
    }

    /**
     * @param Collection $servicePrices
     * @param \Illuminate\Support\Collection $allowServicePrices
     * @return mixed|ServicePrice
     */
    protected function choiceAutoServicePrice(Collection $servicePrices, \Illuminate\Support\Collection $allowServicePrices): ?ServicePrice
    {
        if ($allowServicePrices->count()) {
            $allowServicePrices = $allowServicePrices->sortBy('price');
            /** @var ServicePrice $allowServicePrice */
            $allowServicePrice = $allowServicePrices->first();
        } else {
            $allowServicePrice = $servicePrices->where('is_default', true)->first();
        }

        if ($allowServicePrice && $this->autoSaveServicePrice) {
            SkuServicePrice::updateOrCreate(
                [
                    'tenant_id' => $allowServicePrice->tenant_id,
                    'product_id' => $this->sku->product_id,
                    'sku_id' => $this->sku->id,
                    'service_price_id' => $allowServicePrice->id,
                ],
                [
                    'service_id' => $allowServicePrice->service->id,
                ]
            );
        }

        return $allowServicePrice;
    }
}
