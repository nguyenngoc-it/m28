<?php

namespace Modules\User\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Supplier\Models\Supplier;

/**
 * Class UserSupplier
 *
 * @property int $id
 * @property int $user_id
 * @property int $merchant_id

 * @property-read User|null $user
 * @property-read Supplier|null $supplier
 */
class UserSupplier extends Model
{
    protected $table = 'user_suppliers';

    protected $fillable = [
        'user_id', 'supplier_id'
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
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }
}
