<?php

namespace Modules\ImportHistory\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Merchant\Models\Merchant;
use Modules\User\Models\User;

/**
 * Class ImportHistory
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $creator_id
 * @property int $stock
 * @property string $code
 *
 * @property User $creator
 * @property ImportHistoryItem[]|Collection $items
 */
class ImportHistory extends Model
{
    protected $table = 'import_histories';

    /**
     * @return BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(ImportHistoryItem::class, 'import_history_id', 'id');
    }
}