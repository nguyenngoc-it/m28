<?php
/**
 * Created by PhpStorm.
 * User: vela
 * Date: 5/21/21
 * Time: 11:34 AM
 */

namespace Modules\Warehouse\Models;


use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Merchant\Models\Merchant;

/**
 * Class WarehouseAreaMerchant
 ** @property int $id
 * @property int $user_id
 * @property int $merchant_id
 * @property-read WarehouseArea|null $user
 * @property-read Merchant|null $merchant
 */
class WarehouseAreaMerchant extends Model
{

    protected $table = 'warehouse_area_merchant';

    protected $fillable = [
        'warehouse_area_id', 'merchant_id'
    ];

    /**
     * @return BelongsTo
     */
    public function warehouseArea()
    {
        return $this->belongsTo(WarehouseArea::class, 'warehouse_area_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id', 'id');
    }

}
