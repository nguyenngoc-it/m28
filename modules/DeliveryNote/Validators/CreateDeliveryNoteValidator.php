<?php

namespace Modules\DeliveryNote\Validators;

use App\Base\Validator;
use Modules\Stock\Models\Stock;
use Modules\User\Models\User;

class CreateDeliveryNoteValidator extends Validator
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var array
     */
    protected $skus = [];

    /**
     * @var string[]
     */
    public static $acceptKeys = [
      'warehouse_id',
      'note',
      'skus'
    ];

    /**
     * CreateDeliveryNoteValidator constructor.
     * @param array $input
     * @param User $user
     */
    public function __construct(array $input = [], User $user)
    {
        parent::__construct($input);
        $this->user = $user;
    }

    public function rules()
    {
        return [
          'warehouse_id' => 'required|exists:warehouses,id',
          'skus' => 'required|array|min:1',
          'skus.*.sku_id' => 'required|exists:skus,id',
          'skus.*.warehouse_area_id' => 'required|exists:warehouse_areas,id',
          'skus.*.quantity' => 'required|integer|min:1',
        ];
    }

    protected function customValidate()
    {
        $skus = $this->input['skus'];
        $warehouseId = $this->input['warehouse_id'];
        $skuKeys = [];

        foreach ($skus as $index => $sku) {
            $stock = Stock::query()
                ->where('warehouse_id', $warehouseId)
                ->where('tenant_id', $this->user->tenant_id)
                ->where('warehouse_area_id', $sku['warehouse_area_id'])
                ->where('sku_id', $sku['sku_id'])
                ->first();

            if (!$stock instanceof Stock) {
                $this->errors()->add("skus.$index.stock", static::ERROR_NOT_EXIST);
                return;
            }

            if ($stock->quantity < (int) $sku['quantity']) {
                $this->errors()->add("skus.$index.quantity", static::ERROR_INVALID);
                return;
            }

            $skuKey = "{$sku['sku_id']}_{$sku['warehouse_area_id']}";
            if (in_array($skuKey, $skuKeys)) {
                $this->errors()->add("skus.$index.sku_warehouse_area", static::ERROR_DUPLICATED);
                return;
            }

            $skuKeys[] = $skuKey;
            $this->skus[] = array_merge($sku, ['stock' => $stock]);
        }
    }

    /**
     * @return array
     */
    public function getSkus()
    {
        return $this->skus;
    }
}
