<?php

namespace Modules\Service\Models;

use App\Base\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Merchant\Models\Merchant;

/**
 * Thống kê sử dụng quota của seller,
 * Lưu ý thông tin active_code_id, using_days, using_skus  lấy ở bản ghi có service_combo_price_id = 0
 *
 * Class Service
 * @package Modules\Service\Models
 *
 * @property int id
 * @property int merchant_id
 * @property int active_code_id
 * @property int service_combo_id
 * @property int using_days
 * @property int using_skus
 * @property int service_combo_price_id
 * @property int quota
 * @property Carbon created_at
 *
 * @property Merchant merchant
 * @property ServiceCombo serviceCombo
 *
 */
class ServiceComboMerchantStatistic extends Model
{
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * @return BelongsTo
     */
    public function serviceCombo(): BelongsTo
    {
        return $this->belongsTo(ServiceCombo::class);
    }
}
