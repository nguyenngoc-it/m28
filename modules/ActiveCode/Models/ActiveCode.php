<?php

namespace Modules\ActiveCode\Models;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Modules\Merchant\Models\Merchant;
use Modules\Service\Models\ServiceCombo;
use Modules\Service\Models\ServiceComboMerchantStatistic;

/**
 * Class Service
 * @package Modules\Service\Models
 *
 * @property int id
 * @property int tenant_id
 * @property string code
 * @property string type
 * @property string status
 * @property Carbon expired_at
 *
 * @property Collection serviceComboMerchantStatistics
 *
 */
class ActiveCode extends Model
{
    protected $casts = [
        'expired_at' => 'datetime'
    ];

    /**
     * Trạng thái
     */
    const STATUS_NEW  = 'new';
    const STATUS_USED = 'used';

    /**
     * Loại mã kích hoạt
     */
    const TYPE_SERVICE_COMBO = 'SERVICE_COMBO';

    const TYPE_SERVICE = [
        self::TYPE_SERVICE_COMBO
    ];

    /**
     * @return HasMany
     */
    public function serviceComboMerchantStatistics(): HasMany
    {
        return $this->hasMany(ServiceComboMerchantStatistic::class);
    }

    /**
     *
     * @return ServiceCombo|null
     */
    public function serviceCombo(): ?ServiceCombo
    {
        /** @var ServiceComboMerchantStatistic|null $serviceComboMerchantStatistic */
        $serviceComboMerchantStatistic = $this->serviceComboMerchantStatistics->where('service_combo_price_id', 0)->first();
        return $serviceComboMerchantStatistic ? $serviceComboMerchantStatistic->serviceCombo : null;
    }

    /**
     * @return Merchant|null
     */
    public function merchant(): ?Merchant
    {
        /** @var ServiceComboMerchantStatistic|null $serviceComboMerchantStatistic */
        $serviceComboMerchantStatistic = $this->serviceComboMerchantStatistics->where('service_combo_price_id', 0)->first();
        return $serviceComboMerchantStatistic ? $serviceComboMerchantStatistic->merchant : null;
    }
}
