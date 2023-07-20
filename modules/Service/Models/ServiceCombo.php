<?php

namespace Modules\Service\Models;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Location\Models\Location;
use Modules\Merchant\Models\Merchant;

/**
 * Class Service
 * @package Modules\Service\Models
 *
 * @property int id
 * @property int service_pack_id
 * @property int tenant_id
 * @property int country_id
 * @property string code
 * @property string name
 * @property string note
 * @property int using_days
 * @property int using_skus
 * @property double suggest_price
 * @property Carbon created_at
 *
 * @property Location $country
 * @property Collection $servicePackPrices
 * @property Collection merchants
 */
class ServiceCombo extends Model
{
    protected $casts = [
        'suggest_price' => 'float'
    ];

    /**
     * @return BelongsTo
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'country_id');
    }

    /**
     * @return HasMany
     */
    public function servicePackPrices(): HasMany
    {
        return $this->hasMany(ServicePackPrice::class, 'service_pack_id');
    }

    /**
     * @return HasMany
     */
    public function merchants(): HasMany
    {
        return $this->hasMany(Merchant::class);
    }
}
