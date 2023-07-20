<?php

namespace Modules\Service\Commands;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Modules\Product\Models\Sku;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServicePrice;

class EstimateFee
{
    protected $input;
    protected $services;
    protected $height, $width, $length, $quantity;
    protected $maskSku;

    /**
     * CreateMerchant constructor.
     * @param array $input
     * @param Collection $services
     */
    public function __construct(array $input, Collection $services)
    {
        $this->input               = $input;
        $this->services            = $services;
        $this->height              = (float)Arr::get($this->input, 'height');
        $this->width               = (float)Arr::get($this->input, 'width');
        $this->length              = (float)Arr::get($this->input, 'length');
        $this->quantity            = (int)Arr::get($this->input, 'quantity');
        $sellerRef                 = Arr::get($this->input, 'seller_ref');
        $this->maskSku             = new Sku(['height' => $this->height, 'width' => $this->width, 'length' => $this->length]);
        $this->maskSku->seller_ref = $sellerRef;
    }

    /**
     * @return array
     */
    public function handle()
    {
        if ($this->services->count()) {
            return $this->getEstimateFee();
        }

        return [
            'storage_fee' => 0,
            'return_good_fee' => 0,
            'import_fee' => 0,
            'export_fee' => 0
        ];
    }

    /**
     * @return array
     */
    protected function getEstimateFee()
    {
        /**
         * Phí lưu kho
         */
        $storageFee = 0;
        /** @var Service|null $storageService */
        $storageServices = $this->services->where('type', Service::SERVICE_TYPE_STORAGE);
        /** @var Service $storageService */
        foreach ($storageServices as $storageService) {
            if ($storageService) {
                $storageServicePrice = $this->getNiceServicePrice($storageService);
                if ($storageServicePrice) {
                    $volume     = round($this->width * $this->height * $this->length, 4);
                    $storageFee += round($this->quantity * $volume * $storageServicePrice->price, 2);
                }
            }
        }

        /**
         * Phí hàng hoàn
         */
        $returnGoodFee      = 0;
        $returnGoodServices = $this->services->where('type', Service::SERVICE_TYPE_IMPORTING_RETURN_GOODS);
        /** @var Service $returnGoodService */
        foreach ($returnGoodServices as $returnGoodService) {
            $returnGoodServicePrice = $this->getNiceServicePrice($returnGoodService);
            if ($returnGoodServicePrice) {
                $returnGoodFee += round($this->quantity * $returnGoodServicePrice->price, 2);
            }
        }

        /**
         * Phí nhập
         */
        $importFee      = 0;
        $importServices = $this->services->where('type', Service::SERVICE_TYPE_IMPORT);
        /** @var Service $importService */
        foreach ($importServices as $importService) {
            $importServicePrice = $this->getNiceServicePrice($importService);
            if ($importServicePrice) {
                $importFee += round($this->quantity * $importServicePrice->price, 2);
            }
        }

        /**
         * Phí xuất
         */
        $exportFee      = 0;
        $exportServices = $this->services->where('type', Service::SERVICE_TYPE_EXPORT);
        /** @var Service $importService */
        foreach ($exportServices as $exportService) {
            $exportServicePrice = $this->getNiceServicePrice($exportService);
            if ($exportServicePrice) {
                $exportFee += round($exportServicePrice->price + ($exportServicePrice->yield_price * ($this->quantity - 1)), 2);
            }
        }

        return [
            'storage_fee' => $storageFee,
            'return_good_fee' => $returnGoodFee,
            'import_fee' => $importFee,
            'export_fee' => $exportFee
        ];
    }

    /**
     * @param Service $service
     * @return ServicePrice|null
     */
    protected function getNiceServicePrice(Service $service)
    {
        if ($service->auto_price_by) {
            $servicePrice = \Modules\Service::product()->autoGetSkuServicePrice($this->maskSku, $service, null, false);
        } else {
            $servicePrice = $service->servicePriceDefault();
        }
        return $servicePrice;
    }
}
