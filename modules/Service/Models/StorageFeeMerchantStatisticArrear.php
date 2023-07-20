<?php

namespace Modules\Service\Models;

use App\Base\Model;
use Carbon\Carbon;

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
 * @property int total_volume
 * @property int total_sku
 */
class StorageFeeMerchantStatisticArrear extends Model
{
    protected $casts = [
        'fee' => 'float',
        'fee_paid' => 'float',
        'closing_time' => 'datetime'
    ];
}
