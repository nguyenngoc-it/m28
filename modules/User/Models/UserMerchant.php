<?php

namespace Modules\User\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Merchant\Models\Merchant;

/**
 * Class UserMerchant
 *
 * @property int $id
 * @property int $user_id
 * @property int $merchant_id

 * @property-read User|null $user
 * @property-read Merchant|null $merchant
 */
class UserMerchant extends Model
{
    protected $table = 'user_merchants';

    protected $fillable = [
        'user_id', 'merchant_id'
    ];

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id', 'id');
    }
}