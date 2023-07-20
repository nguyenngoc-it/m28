<?php

namespace Modules\Order\Models;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenant\Models\Tenant;


/**
 * Class OrderTransaction
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $order_id
 * @property Carbon|null $payment_time
 * @property float $amount
 * @property string $method
 * @property string $bank_name
 * @property string $bank_account
 *
 * @property Tenant|null $tenant
 * @property Order|null $order
 */
class OrderTransaction extends Model
{
    protected $table = 'order_transactions';

    protected $casts = [
        'amount' => 'float',
        'payment_time' => 'datetime',
    ];

    const METHOD_CASH = 'CASH'; // Tiên mặt
    const METHOD_BANK_TRANSFER = 'BANK_TRANSFER'; // Chuyển khoản

    public static $methods = [
        self::METHOD_CASH,
        self::METHOD_BANK_TRANSFER,
    ];


    /**
     * @return BelongsTo
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

}