<?php

namespace Modules\Stock\Models;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Sku;
use Modules\User\Models\User;

/**
 * Class StockLog
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $stock_id
 * @property int $sku_id
 * @property string $change
 * @property string $action
 * @property int $quantity
 * @property int|null $real_quantity
 * @property int|null stock_quantity
 * @property string $object_type
 * @property int|string $object_id
 * @property array $payload
 * @property int $creator_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $sign
 * @property string hash
 * @property-read Stock stock
 * @property-read Sku sku
 * @property-read User creator
 */
class StockLog extends Model
{
    protected $table = 'stock_logs';

    protected $casts = [
        'payload' => 'json',
    ];

    const CHANGE_INCREASE = 'INCREASE';
    const CHANGE_DECREASE = 'DECREASE';

    const OBJECT_ORDER                  = 'ORDER';
    const OBJECT_FREIGHT_BILL           = 'FREIGHT_BILL';
    const OBJECT_DOCUMENT               = 'DOCUMENT';
    const OBJECT_PICKING_SESSION_PIECE  = 'PICKING_SESSION_PIECE';
    const OBJECT_PURCHASING_PACKAGE     = 'PURCHASING_PACKAGE';
    const OBJECT_SKU                    = 'SKU';
    const OBJECT_DOCUMENT_SKU_INVENTORY = 'DOCUMENT_SKU_INVENTORY';
    const OBJECT_DOCUMENT_SKU_IMPORTING = 'DOCUMENT_SKU_IMPORTING';
    const OBJECT_PACKAGE                = 'PACKAGE';
    const OBJECT_DELIVERY_NOTE          = 'DELIVERY_NOTE';
    const OBJECT_ORDER_PACKING_ITEM     = 'ORDER_PACKING_ITEM';


    /**
     * @return BelongsTo
     */
    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function sku()
    {
        return $this->belongsTo(Sku::class, 'sku_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    /**
     * @return string
     */
    public function makeSign()
    {
        $data               = $this->only(['id', 'stock_id', 'change', 'action', 'quantity', 'real_quantity', 'object_type', 'object_id', 'creator_id']);
        $data['created_at'] = $this->getAttribute('created_at')->toIso8601ZuluString();

        return hash('sha256', json_encode($data) . config('app.key'));
    }

    /**
     * @return bool
     */
    public function checkSign()
    {
        return $this->makeSign() === $this->getAttribute('sign');
    }
}
