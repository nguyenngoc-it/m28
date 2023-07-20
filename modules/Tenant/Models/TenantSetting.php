<?php

namespace Modules\Tenant\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class TenantSetting
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $key
 * @property array $values
 *
 * @property Tenant $tenant
 */
class TenantSetting extends Model
{
    /**
     * token kết nối app m32
     */
    const M32_APP_CODE   = 'M32_APP_CODE';
    const M32_APP_SECRET = 'M32_APP_SECRET';
    const SKU_MIN_STOCK  = 'SKU_MIN_STOCK'; //số lượng tồn tối thiểu cho sku

    /**
     * Kết nối live chat care soft
     */
    const CARE_SOFT_DOMAIN    = 'CARE_SOFT_DOMAIN';
    const CARE_SOFT_DOMAIN_ID = 'CARE_SOFT_DOMAIN_ID';

    const LOGIN_IMAGE_URL    = 'LOGIN_IMAGE_URL';
    const REGISTER_IMAGE_URL = 'REGISTER_IMAGE_URL';

    const AUTO_CREATE_FREIGHT_BILL = 'AUTO_CREATE_FREIGHT_BILL';
    const DOCUMENT_IMPORTING       = 'DOCUMENT_IMPORTING';

    const ALLOWED_MODULES = 'ALLOWED_MODULES';

    const PUBLISH_EVENT_ORDER_CREATE = 'PUBLISH_EVENT_ORDER_CREATE';
    const PUBLISH_EVENT_ORDER_CHANGE_AMOUNT = 'PUBLISH_EVENT_ORDER_CHANGE_AMOUNT';

    /**
     * @var string
     */
    protected $table = 'tenant_settings';

    protected $casts = [
        'value' => 'json',
    ];


    /**
     * @return BelongsTo
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

}
