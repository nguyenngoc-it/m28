<?php

namespace Modules\Location\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Location
 *
 * @property int $id
 * @property int location_id
 * @property string type
 * @property string parent_code
 * @property string keyword
 *
 * @property Location|null location
 */
class LocationSearch extends Model
{
    protected $table = 'location_searchs';


    /**
     * @return BelongsTo
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
