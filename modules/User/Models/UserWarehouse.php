<?php

namespace Modules\User\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Warehouse\Models\Warehouse;

/**
 * Class UserWarehouse
 *
 * @property int $id
 * @property int $user_id
 * @property int $warehouse_id

 * @property-read User|null $user
 * @property-read Warehouse|null $warehouse
 */
class UserWarehouse extends Model
{
    protected $table = 'user_warehouses';

    protected $fillable = [
        'user_id', 'warehouse_id'
    ];

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }
}