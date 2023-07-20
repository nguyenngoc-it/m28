<?php

namespace Modules\Order\Models;

use App\Base\Model;
use Modules\Product\Models\Sku;
use Modules\Product\Models\SkuCombo;

class OrderSkuComboSku extends Model
{
    protected $table = 'order_sku_combo_skus';


     /**
     * @return BelongsTo
     */
    public function skuCombo()
    {
        return $this->belongsTo(SkuCombo::class, 'sku_combo_id');
    }

     /**
     * @return BelongsTo
     */
    public function sku()
    {
        return $this->belongsTo(Sku::class, 'sku_id');
    }

}
