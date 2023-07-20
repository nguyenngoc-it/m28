<?php

namespace Modules\Locking\Models;

use App\Base\Model;
use Modules\Tenant\Models\Tenant;


/**
 * Class Locking
 * @package Modules\Locking\Models
 *
 * @property integer $id
 * @property integer $id_tenant
 * @property string $key
 */
class Locking extends Model
{
    /**
     * @var string
     */
    protected $table = 'lockings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['tenant_id', 'key'];


    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

}