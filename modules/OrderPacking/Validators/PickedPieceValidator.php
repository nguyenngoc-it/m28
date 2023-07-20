<?php

namespace Modules\OrderPacking\Validators;

use App\Base\Validator;
use Modules\OrderPacking\Models\PickingSessionPiece;
use Modules\Stock\Models\Stock;

class PickedPieceValidator extends Validator
{
    /** @var PickingSessionPiece $pickingSessionPiece */
    protected $pickingSessionPiece;

    /**
     * PickedPieceValidator constructor.
     * @param PickingSessionPiece $pickingSessionPiece
     */
    public function __construct(PickingSessionPiece $pickingSessionPiece)
    {
        parent::__construct([], null);
        $this->pickingSessionPiece = $pickingSessionPiece;
    }

    public function rules()
    {
        return [

        ];
    }

    protected function customValidate()
    {
        /** @var Stock $stockExport */
        $stockExport = Stock::query()->where([
            'warehouse_area_id' => $this->pickingSessionPiece->warehouse_area_id,
            'sku_id' => $this->pickingSessionPiece->sku_id
        ])->first();
        if (empty($stockExport)) {
            $this->errors()->add('stock', static::ERROR_EXISTS);
            return;
        }

        if ($stockExport->real_quantity < $this->pickingSessionPiece->quantity) {
            $this->errors()->add('stock', 'insufficient');
            return;
        }
    }
}
