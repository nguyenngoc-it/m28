<?php

namespace Modules\PurchasingManager\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class PurchasingService
 * @package Modules\PurchasingManager\Models
 *
 * @property int id
 * @property int tenant_id
 * @property string name
 * @property string code
 * @property string base_uri
 * @property string client_id
 * @property string description
 * @property boolean active
 *
 * @property Collection purchasingAccounts
 */
class PurchasingService extends Model
{
    protected $casts = [
        'active' => 'boolean'
    ];

    /**
     * @return HasMany
     */
    public function purchasingAccounts()
    {
        return $this->hasMany(PurchasingAccount::class)->where('purchasing_accounts.status', PurchasingAccount::STATUS_ACTIVE);
    }
}
