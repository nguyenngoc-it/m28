<?php

namespace Modules\Product\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkuComboSku extends Model
{
    protected $table = 'sku_combo_skus';

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
    public function skuCombo()
    {
        return $this->belongsTo(SkuCombo::class, 'sku_combo_id', 'id');
    }

}
