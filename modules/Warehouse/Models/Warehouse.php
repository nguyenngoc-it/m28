<?php

namespace Modules\Warehouse\Models;

use App\Base\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Modules\Location\Models\Location;
use Modules\Tenant\Models\Tenant;

/**
 * Class Warehouse
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $code
 * @property string $name
 * @property boolean $status
 * @property string $description
 * @property int $country_id
 * @property int $province_id
 * @property int $district_id
 * @property int $ward_id
 * @property string $phone
 * @property string $address
 * @property boolean $is_main
 * @property array settings
 *
 * @property Tenant|null $tenant
 * @property Location|null $country
 * @property Location|null $province
 * @property Location|null $district
 * @property Location|null $ward
 * @property Collection $areas
 * @property Collection warehouseAreas
 */
class Warehouse extends Model
{
    protected $table = 'warehouses';

    protected $casts = [
        'status' => 'boolean',
        'is_main' => 'boolean',
        'settings' => 'array'
    ];

    protected $fillable = [
        'tenant_id', 'code', 'name', 'status', 'description', 'country_id',
        'province_id', 'district_id', 'ward_id', 'phone', 'address', 'is_main'
    ];

    /**
     * @return BelongsTo
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function country()
    {
        return $this->belongsTo(Location::class, 'country_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function province()
    {
        return $this->belongsTo(Location::class, 'province_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function district()
    {
        return $this->belongsTo(Location::class, 'district_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function ward()
    {
        return $this->belongsTo(Location::class, 'ward_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function areas()
    {
        return $this->hasMany(WarehouseArea::class, 'warehouse_id');
    }

    /**
     * @return WarehouseArea
     */
    public function getDefaultArea()
    {
        return $this->getAttribute('areas')->firstWhere('code', WarehouseArea::CODE_DEFAULT);
    }

    /**
     * Get setting
     *
     * @param null|string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSetting($key = null, $default = null)
    {
        $settings = $this->getAttribute('settings');
        return Arr::get($settings, $key, $default);
    }

    /**
     * @return HasMany
     */
    public function warehouseAreas()
    {
        return $this->hasMany(WarehouseArea::class);
    }

    /** filter theo warehouse code
     * @param $query
     * @param $warehouseCode
     * @return mixed
     */
    public function scopeCode($query, $warehouseCode)
    {
        if ($warehouseCode) {
            return $query->where('warehouses.code', $warehouseCode);
        } else
            return $query;
    }
    /** filter theo warehouse name
     * @param $query
     * @param $warehouseCode
     * @return mixed
     */
    public function scopeName($query, $warehouseName)
    {
        if ($warehouseName) {
            return $query->where('warehouses.name', $warehouseName);
        } else
            return $query;
    }
    /** filter theo country
     * @param $query
     * @param $warehouseCode
     * @return mixed
     */
    public function scopeCountry($query, $countryId)
    {
        if ($countryId) {
            return $query->where('country_id', $countryId);
        } else
            return $query;
    }
    /** filter theo warehouse status
     * @param $query
     * @param $warehouseCode
     * @return mixed
     */
    public function scopeStatus($query, $warehouseStatus)
    {
        if ($warehouseStatus) {
            return $query->where('warehouses.status', $warehouseStatus);
        } else
            return $query;
    }

    /** filter theo tenantId
     * @param $query
     * @param $tenantId
     * @return mixed
     */
    public function scopeTenant($query, $tenantId)
    {
        if ($tenantId) {
            return $query->where('warehouses.tenant_id', $tenantId);
        } else
            return $query;
    }
}
