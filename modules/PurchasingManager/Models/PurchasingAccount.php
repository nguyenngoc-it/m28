<?php

namespace Modules\PurchasingManager\Models;

use App\Base\Model;
use Carbon\Carbon;
use Gobiz\Activity\Activity;
use Gobiz\Activity\ActivityService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Merchant\Models\Merchant;
use Modules\User\Models\User;

/**
 * Class PurchasingService
 * @package Modules\PurchasingManager\Models
 *
 * @property int id
 * @property int tenant_id
 * @property int merchant_id
 * @property int purchasing_service_id
 * @property string username
 * @property string password
 * @property string pin_code
 * @property string token
 * @property string status
 * @property Carbon refresh_token_at
 * @property Carbon deleted_at
 *
 * @property PurchasingService|null purchasingService
 * @property Merchant|null merchant
 * @property User|null creator
 */
class PurchasingAccount extends Model
{
    use SoftDeletes;

    const STATUS_ACTIVE   = 'active';
    const STATUS_DEACTIVE = 'deactive';
    const STATUS_FAILED   = 'failed'; // Không kết nối được

    const REFRESH_TOKEN_TIME = 80000; // thời gian cache token

    protected $casts = [
        'refresh_token_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * @return BelongsTo
     */
    public function purchasingService()
    {
        return $this->belongsTo(PurchasingService::class);
    }

    /**
     * @return BelongsTo
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * @return BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class);
    }
}
