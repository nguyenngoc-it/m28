<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Modules\Document\Models\ImportingBarcode;
use Modules\Product\Models\Sku;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;

class CreateDocumentImportingValidator extends Validator
{
    /**
     * @var Warehouse
     */
    protected $warehouse;

    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var array
     */
    protected $skuData;

    /**
     * CreateDocumentImportingValidator constructor.
     * @param User $user
     * @param array $input
     */
    public function __construct(User $user, array $input = [])
    {
        $this->tenant = $user->tenant;
        $this->user   = $user;
        parent::__construct($input);
    }

    public function rules()
    {
        return [
            'warehouse_id' => "required",
            'skus' => 'required|array',
            'barcode_type' => 'required|in:' . implode(',', ImportingBarcode::$listTypes),
        ];
    }

    protected function customValidate()
    {
        if (!$this->warehouse = $this->user->warehouses->firstWhere('id', $this->input['warehouse_id'])) {
            $this->errors()->add('warehouse_id', static::ERROR_INVALID);
            return;
        }

        $skus = $this->input['skus'];
        foreach ($skus as $skuData) {
            $skuId    = $skuData['id'];
            $quantity = $skuData['quantity'];

            $sku = Sku::query()->firstWhere(['tenant_id' => $this->tenant->id, 'id' => $skuId]);
            if (!$sku instanceof Sku) {
                $this->errors()->add('sku_invalid', $skuId);
                return;
            }
            $quantity = intval($quantity);
            if ($quantity < 0) {
                $this->errors()->add('quantity_invalid', $sku->code);
                return;
            }

            $this->skuData[] = [
                'sku' => $sku,
                'quantity' => $quantity
            ];
        }
    }


    /**
     * @return array
     */
    public function getSkuData()
    {
        return $this->skuData;
    }

    /**
     * @return Warehouse
     */
    public function getWarehouse()
    {
        return $this->warehouse;
    }
}
