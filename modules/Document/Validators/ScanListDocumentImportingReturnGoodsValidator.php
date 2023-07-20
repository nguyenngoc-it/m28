<?php

namespace Modules\Document\Validators;

use App\Base\Validator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Order\Models\Order;

class ScanListDocumentImportingReturnGoodsValidator extends Validator
{
    /** @var Collection */
    protected $orders;

    public function rules(): array
    {
        return [
            'ids' => 'required|array',
        ];
    }

    public function customValidate()
    {
        $ids          = $this->input('ids');
        $this->orders = Order::query()->whereIn('id', $ids)->where('tenant_id', $this->user->tenant_id)
            ->with(['importReturnGoodsServicePrices'])
            ->get();
        if ($this->orders->count() !== count($ids)) {
            $this->errors()->add('ids', static::ERROR_INVALID);
        }
    }

    /**
     * @return Collection
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

}
