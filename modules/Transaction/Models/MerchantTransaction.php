<?php

namespace Modules\Transaction\Models;

use App\Base\Model;

/**
 * Class Transaction
 *
 * @property int id
 * @property int merchant_id
 * @property string type
 * @property string object_type
 * @property int object_id
 * @property double amount
 * @property array metadata
 * @property string trans_id
 * @property string m4_trans_id
 *
 */
class MerchantTransaction extends Model
{
    protected $table = 'merchant_transactions';

    protected $casts = [
        'metadata' => 'array'
    ];

    const ACTION_COLLECT = 'collect';
    const ACTION_REFUND  = 'refund';
}
