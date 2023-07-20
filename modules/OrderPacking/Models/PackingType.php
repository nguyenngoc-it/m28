<?php

namespace Modules\OrderPacking\Models;

use App\Base\Model;
use Modules\Tenant\Models\Tenant;

/**
 * Class PackingType
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 *
 * @property-read  Tenant|null $tenant
 */
class PackingType extends Model
{
    protected $table = 'packing_types';


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}