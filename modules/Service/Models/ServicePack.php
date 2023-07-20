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
 * @property int tenant_id
 * @property int country_id
 * @property string code
 * @property string name
 * @property string note
 * @property Carbon created_at
 *
 * @property Location $country
 * @property Collection $servicePackPrices
 * @property Collection merchants
 * @property Collection serviceCombos
 */
class ServicePack extends Model
{

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
        return $this->hasMany(ServicePackPrice::class);
    }

    /**
     * @return HasMany
     */
    public function merchants(): HasMany
    {
        return $this->hasMany(Merchant::class);
    }

    /**
     * @return HasMany
     */
    public function serviceCombos(): HasMany
    {
        return $this->hasMany(ServiceCombo::class);
    }
}
