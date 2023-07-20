<?php

namespace Modules\Service\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Location\Models\Location;
use Modules\Tenant\Models\Tenant;

/**
 * Class Service
 * @package Modules\Service\Models
 *
 * @property int id
 * @property int tenant_id
 * @property int country_id
 * @property string type
 * @property string code
 * @property string name
 * @property string status
 * @property string auto_price_by
 * @property boolean is_required
 *
 * @property Collection servicePrices
 * @property Tenant|null $tenant
 * @property Location|null country
 */
class Service extends Model
{
    protected $casts = [
        'is_required' => 'boolean'
    ];

    /**
     * Trạng thái
     */
    const STATUS_ACTIVE   = 'ACTIVE';
    const STATUS_INACTIVE = 'INACTIVE';

    /**
     * Kiểu dịch vụ
     */
    const SERVICE_TYPE_IMPORT                 = 'IMPORT';
    const SERVICE_TYPE_EXPORT                 = 'EXPORT';
    const SERVICE_TYPE_IMPORTING_RETURN_GOODS = 'IMPORTING_RETURN_GOODS'; //nhập hàng hoàn
    const SERVICE_TYPE_STORAGE                = 'STORAGE';
    const SERVICE_TYPE_TRANSPORT              = 'TRANSPORT';
    const SERVICE_TYPE_EXTENT                 = 'EXTENT'; // Dịch vụ khác

    /**
     * Kiểu tự động chọn giá dịch vụ
     * Khi tạo/chỉnh sửa sản phẩm sẽ ưu tiên tự chọn mức giá tự động trước, nếu không chọn được mới chọn mức giá mặc định
     */
    const SERVICE_AUTO_PRICE_BY_SIZE   = 'SIZE'; // Tự động chọn theo kích thước dài rộng cao
    const SERVICE_AUTO_PRICE_BY_VOLUME = 'VOLUME'; // Tự động chọn theo volume
    const SERVICE_AUTO_PRICE_BY_SELLER = 'SELLER'; // Tự động chọn theo mã seller (mã giới thiệu seller)

    /**
     * Thể hiện dịch vụ
     */
    const SERVICE_PRICE_DEFAULT = 'default';

    public static $groupForPacks = [
        self::SERVICE_TYPE_IMPORT,
        self::SERVICE_TYPE_EXPORT,
        self::SERVICE_TYPE_STORAGE,
        self::SERVICE_TYPE_IMPORTING_RETURN_GOODS
    ];

    /**
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

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
    public function servicePrices(): HasMany
    {
        return $this->hasMany(ServicePrice::class, 'service_code', 'code')->where('tenant_id', $this->getAttribute('tenant_id'));
    }

    /**
     * @return ServicePrice|null
     */
    public function servicePriceDefault(): ?ServicePrice
    {
        return $this->servicePrices->where('is_default', true)->first();
    }
}
