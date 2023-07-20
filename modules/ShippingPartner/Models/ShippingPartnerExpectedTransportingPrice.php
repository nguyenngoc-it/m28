<?php

namespace Modules\ShippingPartner\Models;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ShippingPartnerExpectedTransportingPrice
 * @package Modules\ShippingPartner\Models
 *
 * @property int id
 * @property int tenant_id
 * @property double max_weight
 * @property double price
 * @property int shipping_partner_id
 * @property int sender_ward_id
 * @property int sender_district_id
 * @property int sender_province_id
 * @property int receiver_ward_id
 * @property int receiver_district_id
 * @property int receiver_province_id
 * @property string sender_ward_code
 * @property string sender_district_code
 * @property string sender_province_code
 * @property string receiver_ward_code
 * @property string receiver_district_code
 * @property string receiver_province_code
 * @property boolean mapped
 * @property Carbon created_at
 *
 * @property ShippingPartner|null shippingPartner
 */
class ShippingPartnerExpectedTransportingPrice extends Model
{
    protected $table = 'shipping_partner_expected_transporting_prices';

    protected $casts = [
        'max_weight' => 'float',
        'price' => 'float',
        'mapped' => 'boolean'
    ];

    /**
     * @return BelongsTo
     */
    public function shippingPartner()
    {
        return $this->belongsTo(ShippingPartner::class);
    }
}
