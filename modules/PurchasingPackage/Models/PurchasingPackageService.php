<?php

namespace Modules\PurchasingPackage\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServicePrice;

/**
 * Class PurchasingPackageService
 *
 * @property int id
 * @property int purchasing_package_id
 * @property int service_id
 * @property int service_price_id
 * @property float price
 * @property int quantity
 * @property float amount
 * @property array skus
 *
 * @property PurchasingPackage|null purchasingPackage
 * @property Service|null service
 * @property ServicePrice|null servicePrice
 */
class PurchasingPackageService extends Model
{
    protected $table = 'purchasing_package_services';

    protected $casts = [
        'skus' => 'json'
    ];

    public function getSkusAttribute($value)
    {
        return (array)json_decode($value, true);
    }

    /**
     * @return BelongsTo
     */
    public function purchasingPackage()
    {
        return $this->belongsTo(PurchasingPackage::class, 'purchasing_package_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function servicePrice()
    {
        return $this->belongsTo(ServicePrice::class, 'service_price_id', 'id');
    }
}
