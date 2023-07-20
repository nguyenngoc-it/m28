<?php

namespace Modules\Order\Models;

use App\Base\Model;
use Modules\Product\Models\SkuCombo;

class OrderSkuCombo extends Model
{
    protected $table = 'order_sku_combos';


     /**
     * @return BelongsTo
     */
    public function skuCombo()
    {
        return $this->belongsTo(SkuCombo::class, 'sku_combo_id', 'id');
    }

}
