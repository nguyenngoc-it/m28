<?php

namespace Modules\Merchant\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Merchant\Models\Merchant;

/**
 * Class MerchantSetting
 *
 * @property int $id
 * @property int $merchant_id
 * @property string $key
 * @property array $values
 *
 * @property Merchant $Merchant
 */
class MerchantSetting extends Model
{
    const SETTING_NOT_AUTO_CREATE_FREIGHT_BILL = 'NOT_AUTO_CREATE_FREIGHT_BILL';

    /**
     * @var string
     */
    protected $table = 'merchant_settings';

    protected $casts = [
        'value' => 'json',
    ];


    /**
     * @return BelongsTo
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id', 'id');
    }

}
