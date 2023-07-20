<?php

namespace Modules\Service\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Location\Models\Location;

/**
 * Class Service
 * @package Modules\Service\Models
 *
 * @property int id
 * @property int tenant_id
 * @property int country_id
 * @property string service_code
 * @property boolean is_default
 * @property string label
 * @property double price
 * @property double yield_price
 * @property float deduct
 * @property string note
 * @property float height
 * @property float width
 * @property float length
 * @property float volume
 * @property array seller_refs
 * @property array seller_codes
 *
 * @property Service service
 * @property Location|null country
 */
class ServicePrice extends Model
{
    protected $casts = [
        'price' => 'float',
        'yield_price' => 'float',
        'deduct' => 'float',
        'is_default' => 'boolean',
        'seller_refs' => 'array',
        'seller_codes' => 'array',
    ];

    public function getSellerRefsAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        return json_decode($value, true);
    }

    public function getSellerCodesAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        return json_decode($value, true);
    }

    /**
     * @return BelongsTo
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_code', 'code')->where('tenant_id', $this->tenant_id);
    }

    /**
     * @return BelongsTo
     */
    public function serviceRelate(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_code', 'code');
    }

    /**
     * @return BelongsTo
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'country_id');
    }
}
