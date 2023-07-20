<?php

namespace Modules\Warehouse\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Merchant\Models\Merchant;

/**
 * Class WarehouseArea
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $warehouse_id
 * @property int $merchant_id
 * @property string $code
 * @property string $name
 * @property string $description
 * @property boolean $status
 * @property Warehouse $warehouse
 */
class WarehouseArea extends Model
{
    protected $table = 'warehouse_areas';

    const CODE_DEFAULT = 'default';

    const STATUS_HIDDEN = 'HIDDEN';

    /**
     * @return BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }
}
