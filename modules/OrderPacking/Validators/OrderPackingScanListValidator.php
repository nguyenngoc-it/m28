<?php

namespace Modules\OrderPacking\Validators;

use App\Base\Validator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Order\Models\Order;
use Modules\OrderPacking\Models\OrderPacking;
use Modules\Tenant\Models\Tenant;
use Modules\Warehouse\Models\Warehouse;

class OrderPackingScanListValidator extends Validator
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
     * @var OrderPacking[] | Collection
     */
    protected $orderPackings;

    /**
     * OrderPackingScanValidator constructor.
     * @param Tenant $tenant
     * @param array $input
     */
    public function __construct(Tenant $tenant, array $input = [])
    {
        $this->tenant = $tenant;
        parent::__construct($input);
    }

    public function rules()
    {
        return [
            'warehouse_id' => 'required',
            'ids' => 'required|array',
        ];
    }

    protected function customValidate()
    {
        if (!$this->warehouse = $this->tenant->warehouses()->firstWhere(['id' => $this->input['warehouse_id']])) {
            $this->errors()->add('warehouse_id', static::ERROR_INVALID);
            return;
        }

        $ids                 = (array)($this->input['ids']);
        $this->orderPackings = OrderPacking::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->whereIn('id', $ids)
            ->with(['freightBill', 'order', 'orderPackingItems', 'shippingPartner'])
            ->get();
    }

    /**
     * @return array
     */
    public function getOrderPackings()
    {
        return $this->orderPackings;
    }
}
