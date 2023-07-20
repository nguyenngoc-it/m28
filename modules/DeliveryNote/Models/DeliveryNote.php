<?php

namespace Modules\DeliveryNote\Models;

use App\Base\Model;
use Modules\Stock\Models\StockLog;
use Modules\Stock\Services\StockObjectInterface;
use Modules\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

/**
 * Class DeliveryNote
 *
 * @property int $warehouse_id
 * @property string $note
 * @property int $creator_id
 *
 */
class DeliveryNote extends Model implements StockObjectInterface
{
    protected $table = 'delivery_notes';

    /**
     * @return mixed|string
     */
    public function getObjectId()
    {
        return $this->getKey();
    }

    /**
     * @return string
     */
    public function getObjectType()
    {
        return StockLog::OBJECT_DELIVERY_NOTE;
    }

    /**
     * @return BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }
}
