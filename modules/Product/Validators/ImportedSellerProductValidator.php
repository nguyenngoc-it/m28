<?php

namespace Modules\Product\Validators;

use App\Base\Validator;
use Gobiz\Support\Helper;
use Modules\Product\Models\Product;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServicePrice;

class ImportedSellerProductValidator extends Validator
{
    /**
     * @var ServicePrice|null
     */
    protected $servicePrice;
    /** @var array */
    protected $servicePriceExports = [];
    protected $servicePriceImports = [];

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'product_code' => 'required',
            'product_name' => 'required',
            'price' => '',
            'service_shipping' => '',
            'weight' => '',
            'length' => '',
            'width' => '',
            'height' => '',
            'service_importing' => 'string',
            'service_exporting' => 'string',
        ];
    }

    /**
     * @return array
     */
    public function getServicePriceExports(): array
    {
        return $this->servicePriceExports;
    }

    /**
     * @return array
     */
    public function getServicePriceImports(): array
    {
        return $this->servicePriceImports;
    }

    /**
     * @return ServicePrice|null
     */
    public function getServicePrice(): ?ServicePrice
    {
        return $this->servicePrice;
    }

    protected function customValidate()
    {
        $productCode = $this->input('product_code');
        $product     = Product::query()->where([
            'code' => $productCode,
            'tenant_id' => $this->user->tenant_id,
            'merchant_id' => $this->user->merchant->id
        ])->first();
        if ($product) {
            $this->errors()->add('product_code', static::ERROR_ALREADY_EXIST);
            return;
        }

        $allServices = $this->user->tenant->services;

        /**
         * Kiểm tra dịch vụ vận chuyển truyền lên có hợp lệ hay không
         */
        $serviceShippingName = trim($this->input('service_shipping'));
        if ($serviceShippingName) {
            $allShippingServices = $allServices->where('type', Service::SERVICE_TYPE_TRANSPORT)->where('status', Service::STATUS_ACTIVE);
            $valid               = false;
            /** @var Service|null $serviceShipping */
            foreach ($allShippingServices as $serviceShipping) {
                if (Helper::convert_vi_to_en($serviceShippingName) == Helper::convert_vi_to_en($serviceShipping->name)) {
                    $valid              = true;
                    $this->servicePrice = $serviceShipping->servicePriceDefault();
                }
            }
            if (!$valid || !$this->servicePrice) {
                $this->errors()->add('service_shipping', 'not_valid');
                return;
            }
        }

        /**
         * Kiểm tra dịch vụ nhập truyền lên
         */
        $serviceImports = $this->input('service_importing');
        if ($serviceImports) {
            $serviceImports       = explode(',', $serviceImports);
            $allImportingServices = $allServices->where('type', Service::SERVICE_TYPE_IMPORT)->where('status', Service::STATUS_ACTIVE);
            /** @var Service $allImportingService */
            foreach ($allImportingServices as $allImportingService) {
                foreach ($serviceImports as $serviceImport) {
                    $serviceImportName = Helper::convert_vi_to_en($serviceImport);
                    if (Helper::convert_vi_to_en($allImportingService->name) == $serviceImportName) {
                        $this->servicePriceImports[] = $allImportingService->servicePriceDefault();
                    }
                }
            }
            if (count($serviceImports) < count($this->servicePriceExports)) {
                $this->errors()->add('service_importing', 'not_valid');
                return;
            }
        }

        /**
         * Kiểm tra dịch vụ xuất truyền lên
         */
        $serviceExports = $this->input('service_exporting');
        if ($serviceExports) {
            $serviceExports       = explode(',', $serviceExports);
            $allExportingServices = $allServices->where('type', Service::SERVICE_TYPE_EXPORT)->where('status', Service::STATUS_ACTIVE);
            /** @var Service $allExportingService */
            foreach ($allExportingServices as $allExportingService) {
                foreach ($serviceExports as $serviceExport) {
                    $serviceExportName = Helper::convert_vi_to_en($serviceExport);
                    if (Helper::convert_vi_to_en($allExportingService->name) == $serviceExportName) {
                        $this->servicePriceImports[] = $allExportingService->servicePriceDefault();
                    }
                }
            }
            if (count($serviceImports) < count($this->servicePriceExports)) {
                $this->errors()->add('service_exporting', 'not_valid');
                return;
            }
        }
    }
}
