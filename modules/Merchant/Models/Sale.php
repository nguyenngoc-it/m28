<?php

namespace Modules\Merchant\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * class Merchant
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $merchant_id
 * @property string $username
 * @property string $email
 * @property datetime $created_at
 * @property datetime $updated_at
 * @property-read Sale[]|Collection $sales
 */
class Sale extends Model
{
    protected $table = 'sales';

    /**
     * @return BelongsTo
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id', 'id');
    }
}
