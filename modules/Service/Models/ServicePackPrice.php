<?php

namespace Modules\Service\Models;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Service
 * @package Modules\Service\Models
 *
 * @property int id
 * @property int service_pack_id
 * @property int service_price_id
 * @property string type
 * @property int service_id
 * @property Carbon created_at
 *
 * @property Service $service
 * @property ServicePrice $servicePrice
 * @property ServicePack $servicePack
 */
class ServicePackPrice extends Model
{

    /**
     * @return BelongsTo
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsTo
     */
    public function servicePrice(): BelongsTo
    {
        return $this->belongsTo(ServicePrice::class);
    }

    /**
     * @return BelongsTo
     */
    public function servicePack(): BelongsTo
    {
        return $this->belongsTo(ServicePack::class);
    }
}
