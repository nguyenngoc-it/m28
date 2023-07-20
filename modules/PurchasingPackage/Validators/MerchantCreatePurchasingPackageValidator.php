<?php

namespace Modules\PurchasingPackage\Validators;

use App\Base\Validator;
use Modules\Product\Models\ProductMerchant;
use Modules\Product\Models\Sku;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Warehouse\Models\Warehouse;
use Modules\Service\Models\Service as ServiceModel;

class MerchantCreatePurchasingPackageValidator extends Validator
{
    /**
     * MerchantCreatePurchasingPackageValidator constructor.
     * @param array $input
     */
    public function __construct(array $input)
    {
        parent::__construct($input);
    }

    /**
     * @var Warehouse
     */
    protected $destinationWarehouse;

    /**
     * @var ShippingPartner|null
     */
    protected $shippingPartner;

    /**
     * @var array
     */
    protected $packageItems;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'destination_warehouse_id' => 'required',
            'freight_bill_code' => 'string',
            'package_items' => 'required|array',
            'weight' => 'numeric|gt:0',
            'service_ids' => 'array',
            'shipping_partner_id' => 'int',
            'note' => 'string'
        ];
    }

    protected function customValidate()
    {
        if (
            !empty($this->input['shipping_partner_id']) &&
            !$this->shippingPartner = $this->user->tenant->shippingPartners()->firstWhere('id', $this->input('shipping_partner_id'))
        ) {
            $this->errors()->add('shipping_partner_id', static::ERROR_NOT_EXIST);
            return;
        };

        if (
            !empty($this->input['destination_warehouse_id']) &&
            !$this->destinationWarehouse = $this->user->tenant->warehouses()->firstWhere('id', $this->input('destination_warehouse_id'))
        ) {
            $this->errors()->add('destination_warehouse_id', static::ERROR_NOT_EXIST);
            return;
        };

        if (
            !empty($this->input['freight_bill_code']) &&
            (
                PurchasingPackage::query()->where('freight_bill_code', trim($this->input['freight_bill_code']))
                    ->where('merchant_id', $this->user->merchant->id)->count() > 0
            )
        ) {
            $this->errors()->add('freight_bill_code', static::ERROR_ALREADY_EXIST);
            return;
        };

        $errors            = [];
        $packageItemErrors = $this->validatePackageItems();
        if (!empty($packageItemErrors)) {
            $this->errors()->add('package_items', $packageItemErrors);
        }
        if (empty($this->packageItems)) {
            $this->errors()->add('package_items', static::ERROR_REQUIRED);
            return;
        }

        $servicesErrors = $this->validateServices();
        if (!$servicesErrors) {
            return;
        }

        if (!empty($errors)) {
            $this->errors()->add('errors', $errors);
        }

        if (!empty($errors) || !empty($skuErrors) || !empty($servicesErrors)) {
            return;
        }
    }

    /**
     * @return bool|void
     */
    protected function validateServices()
    {
        if (!empty($this->input['service_ids'])) {
            $serviceIds = $this->input['service_ids'];
            foreach ($serviceIds as $serviceId) {
                $service = $this->user->tenant->services()->where('id', intval(trim($serviceId)))
                    ->where('type', ServiceModel::SERVICE_TYPE_IMPORT)
                    ->where('status', ServiceModel::STATUS_ACTIVE)->first();

                if (!$service instanceof ServiceModel) {
                    $this->errors()->add('service_ids', static::ERROR_NOT_EXIST);
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return array
     */
    protected function validatePackageItems()
    {
        $packageItems = $this->input['package_items'];
        $SkuRequired  = ['sku_id', 'quantity'];
        $line         = 0;
        $skuErrors    = [];
        $skuIds       = [];
        foreach ($packageItems as $packageItem) {
            $line++;
            $lineKey = 'line_' . $line;
            foreach ($SkuRequired as $key) {
                if (!isset($packageItem[$key])) {
                    $skuErrors[$lineKey][self::ERROR_REQUIRED][] = $key;
                    continue;
                }
            }

            $quantity = floatval($packageItem['quantity']);
            if ($quantity <= 0) {
                $skuErrors[$lineKey][self::ERROR_INVALID][] = 'quantity';
            }

            $skuId = trim($packageItem['sku_id']);
            $sku   = $this->user->tenant->skus()->firstWhere('id', $skuId);

            if (!$sku instanceof Sku || $sku->status == Sku::STATUS_STOP_SELLING) {
                $skuErrors[$lineKey][self::ERROR_INVALID][] = 'sku_id';
            } else {
                $productMerchant = ProductMerchant::query()
                    ->where('product_id', $sku->product_id)
                    ->where('merchant_id', $this->user->merchant->id)
                    ->first();
                if (!$productMerchant) {
                    $skuErrors[$lineKey][self::ERROR_INVALID][] = 'sku_id';
                }
            }

            if (in_array($skuId, $skuIds)) {
                $skuErrors[$lineKey][self::ERROR_ALREADY_EXIST][] = 'sku_id';
            }

            if (!empty($skuErrors[$lineKey])) {
                continue;
            }

            $this->packageItems[] = [
                'sku_id' => $sku->id,
                'quantity' => $quantity,
                'received_quantity' => null,
            ];
            $skuIds[]             = $skuId;
        }

        return $skuErrors;
    }

    /**
     * @return array
     */
    public function getPackageItems()
    {
        return $this->packageItems;
    }

    /**
     * @return ShippingPartner|null
     */
    public function getShippingPartner()
    {
        return $this->shippingPartner;
    }

    /**
     * @return Warehouse|null
     */
    public function getDestinationWarehouse()
    {
        return $this->destinationWarehouse;
    }
}
