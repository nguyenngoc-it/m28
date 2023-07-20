<?php

namespace Modules\Store\Models;

use App\Base\Model;
use Carbon\Carbon;
use Modules\Product\Models\Sku;
use Modules\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class StoreSku
 *
 * @property int id
 * @property int tenant_id
 * @property int store_id
 * @property int sku_id
 * @property int product_id
 * @property string sku_id_origin
 * @property string product_id_origin
 * @property string marketplace_code
 * @property string marketplace_store_id
 * @property string code

 * @property Carbon created_at
 * @property Carbon updated_at
 *
 * @property-read Tenant tenant
 * @property-read Sku sku
 * @property-read Store store
 */
class StoreSku extends Model
{
    protected $table = 'store_skus';

    /**
     * @return BelongsTo
     */
    public function sku()
    {
        return $this->belongsTo(Sku::class, 'sku_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }
}
