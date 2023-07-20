<?php

namespace Modules\PurchasingPackage\Validators;

use App\Base\Validator;
use Modules\Auth\Services\Permission;
use Modules\Product\Models\ProductMerchant;
use Modules\Product\Models\Sku;
use Modules\PurchasingPackage\Models\PurchasingPackage;
use Modules\ShippingPartner\Models\ShippingPartner;
use Modules\Warehouse\Models\Warehouse;
use Modules\Service\Models\Service as ServiceModel;

class CreatePurchasingPackageValidator extends Validator
{
    /**
     * CreatePurchasingPackageValidator constructor.
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
                    ->where('tenant_id', $this->user->tenant_id)->count() > 0
            )
        ) {
            $this->errors()->add('freight_bill_code', static::ERROR_ALREADY_EXIST);
            return;
        };

        $packageItemErrors = $this->validatePackageItems();
        if (!$packageItemErrors) {
            return;
        }

        if (empty($this->packageItems)) {
            $this->errors()->add('package_items', static::ERROR_REQUIRED);
            return;
        }
    }

    /**
     * @return array
     */
    protected function validatePackageItems()
    {
        $packageItems = $this->input['package_items'];
        $SkuRequired  = ['sku_id', 'quantity'];
        $line         = 0;
        $skuIds       = [];
        foreach ($packageItems as $packageItem) {
            $line++;
            $lineKey = 'line_' . $line;
            $skuId   = isset($packageItem['sku_id']) ? trim($packageItem['sku_id']) : null;
            foreach ($SkuRequired as $key) {
                if (!isset($packageItem[$key])) {
                    $this->errors()->add($key.'_required', ['sku' => $skuId, 'line' => $lineKey]);
                    return false;
                }
            }

            $sku     = $this->user->tenant->skus()->firstWhere('id', $skuId);
            $skuCode = ($sku instanceof Sku) ? $sku->code : $skuId;

            if (!$sku instanceof Sku || $sku->status == Sku::STATUS_STOP_SELLING) {
                $this->errors()->add('sku_invalid', ['sku' => $skuCode, 'line' => $lineKey]);
                return false;
            } else if (!$this->user->can(Permission::PRODUCT_MANAGE_ALL)) {
                $productMerchant = ProductMerchant::query()
                    ->where('product_id', $sku->product_id)
                    ->whereIn('merchant_id', $this->user->merchants->pluck('id')->all())
                    ->first();
                if (!$productMerchant) {
                    $this->errors()->add('sku_invalid', ['sku' => $skuCode, 'line' => $lineKey]);
                    return false;
                }
            }

            $quantity = floatval($packageItem['quantity']);
            if ($quantity <= 0) {
                $this->errors()->add('quantity_invalid', ['sku' => $skuCode, 'line' => $lineKey]);
                return false;
            }

            if (in_array($skuId, $skuIds)) {
                $this->errors()->add('sku_already_exist', ['sku' => $skuCode, 'line' => $lineKey]);
                return false;
            }

            $this->packageItems[] = [
                'sku_id' => $sku->id,
                'quantity' => $quantity,
                'received_quantity' => null,
            ];
            $skuIds[]             = $skuId;
        }

        return true;
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
