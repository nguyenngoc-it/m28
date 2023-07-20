<?php

namespace Modules\Category\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Location\Models\Location;
use Modules\Tenant\Models\Tenant;

/**
 * Class Category
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $parent_id
 * @property string $code
 * @property string $name
 * @property string $note
 * @property int $position
 * @property Category|null $parent
 *
 * @property Tenant|null $tenant
 */
class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'tenant_id', 'code', 'name','parent_id', 'note'
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
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id', 'id');
    }

    /** filter theo tenantId
     * @param $query
     * @param $tenantId
     * @return mixed
     */
    public function scopeTenant($query, $tenantId)
    {
        if ($tenantId) {
            return $query->where('categories.tenant_id', $tenantId);
        } else
            return $query;
    }

    public function scopeCode($query, $code)
    {
        if ($code) {
            return $query->where('categories.code', $code);
        } else
            return $query;
    }
}