<?php

namespace Modules\OrderPacking\Validators;

use App\Base\Validator;
use Modules\OrderPacking\Models\PickingSession;

class GettingProcessingPickingSessionValidator extends Validator
{

    /** @var PickingSession|null */
    protected $processingPickingSession;

    public function rules()
    {
        return [
            'warehouse_id' => 'required'
        ];
    }

    /**
     * @return PickingSession|null
     */
    public function getProcessingPickingSession(): ?PickingSession
    {
        return $this->processingPickingSession;
    }

    protected function customValidate()
    {
        $warehouseId = $this->input('warehouse_id');
        if (!in_array($warehouseId, $this->user->warehouses->pluck('id')->all())) {
            $this->errors()->add('warehouse_id', static::ERROR_EXISTS);
            return;
        }
        $this->processingPickingSession = $this->user->pickingSessions->where('is_picked', false)->first();
    }
}
