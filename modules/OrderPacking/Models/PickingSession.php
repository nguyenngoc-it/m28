<?php

namespace Modules\OrderPacking\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\User\Models\User;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseArea;

/**
 * Class PickingSession
 * @package Modules\OrderPacking\Models
 *
 * @property int id
 * @property int warehouse_id
 * @property int warehouse_area_id
 * @property int picker_id
 * @property int order_quantity
 * @property int order_packed_quantity
 * @property boolean is_picked
 *
 * @property Warehouse warehouse
 * @property WarehouseArea warehouseArea
 * @property User picker
 *
 * @property Collection pickingSessionPieces
 * @property Collection orderPackings
 */
class PickingSession extends Model
{
    protected $casts = [
        'is_picked' => 'boolean'
    ];

    /**
     * @return BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo
     */
    public function warehouseArea()
    {
        return $this->belongsTo(WarehouseArea::class);
    }

    /**
     * @return BelongsTo
     */
    public function picker()
    {
        return $this->belongsTo(User::class, 'picker_id');
    }

    /**
     * @return HasMany
     */
    public function pickingSessionPieces()
    {
        return $this->hasMany(PickingSessionPiece::class);
    }

    /**
     * @return HasMany
     */
    public function orderPackings()
    {
        return $this->hasMany(OrderPacking::class);
    }
}
