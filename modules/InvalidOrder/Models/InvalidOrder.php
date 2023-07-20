<?php

namespace Modules\InvalidOrder\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

/**
 * Class InvalidOrder
 *
 * @property int id
 * @property int tenant_id
 * @property string source
 * @property string code
 * @property array payload
 * @property string error_code
 * @property array errors
 * @property int creator_id
 * @property-read  User creator
 */
class InvalidOrder extends Model
{
    protected $table = 'invalid_orders';

    protected $casts = [
        'payload' => 'json',
        'errors' => 'array',
    ];

    const SOURCE_INTERNAL_API = 'INTERNAL_API';
    const SOURCE_SHOPEE = 'SHOPEE';
    const SOURCE_OTHER = 'OTHER';
    const SOURCE_KIOTVIET = 'KIOTVIET';
    const SOURCE_LAZADA = 'LAZADA';
    const SOURCE_SHOPBASE = 'SHOPBASE';

    const ERROR_SKU_UNMAPPED = 'SKU_UNMAPPED'; // Sku chưa được map
    const ERROR_MERCHANT_UNMAPPED = 'MERCHANT_UNMAPPED'; // Merchant chưa được map
    const ERROR_TECHNICAL = 'TECHNICAL'; // Lỗi kỹ thuật

    /**
     * @return BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }
}
