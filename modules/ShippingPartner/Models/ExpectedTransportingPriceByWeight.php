<?php

namespace Modules\ShippingPartner\Models;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ExpectedTransportingPriceByWeight
 * @package Modules\ShippingPartner\Models
 *
 * @property int id
 * @property int tenant_id
 * @property double max_weight
 * @property double price
 * @property int shipping_partner_id
 * @property int warehouse_id
 * @property int receiver_ward_id
 * @property string receiver_ward
 * @property int receiver_district_id
 * @property string receiver_district
 * @property int receiver_province_id
 * @property string receiver_province
 * @property Carbon created_at
 *
 * @property ShippingPartner|null shippingPartner
 */
class ExpectedTransportingPriceByWeight extends Model
{
    protected $table = 'expected_transporting_price_by_weights';

    protected $casts = [
        'max_weight' => 'float',
        'price' => 'float',
        'unit_price' => 'float'
    ];

    /**
     * @return BelongsTo
     */
    public function shippingPartner(): BelongsTo
    {
        return $this->belongsTo(ShippingPartner::class);
    }
}
