<?php

namespace Modules\Service\Models;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Merchant\Models\Merchant;

/**
 * Class StorageFeeSkuStatistic
 * @package Modules\Service\Models
 *
 * @property int id
 * @property int merchant_id
 * @property Carbon closing_time
 * @property float fee
 * @property float fee_paid
 * @property string trans_m4_id
 * @property string trans_id
 * @property int total_volume
 * @property int total_sku
 * @property array snapshot_skus
 *
 * @property Merchant merchant
 */
class StorageFeeMerchantStatistic extends Model
{
    protected $casts = [
        'fee' => 'float',
        'fee_paid' => 'float',
        'closing_time' => 'datetime',
        'snapshot_skus' => 'json'
    ];

    /**
     * @return BelongsTo
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}
