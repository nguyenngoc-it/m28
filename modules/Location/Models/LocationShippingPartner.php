<?php

namespace Modules\Location\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ShippingPartner\Models\ShippingPartner;

/**
 * Class LocationShippingPartner
 *
 * @property int $id
 * @property int location_id
 * @property int shipping_partner_id
 *
 * @property Location|null location
 */
class LocationShippingPartner extends Model
{
    protected $table = 'location_shipping_partners';

    /**
     * @return BelongsTo
     */
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function shippingPartner()
    {
        return $this->belongsTo(ShippingPartner::class, 'shipping_partner_id', 'id');
    }
}
