<?php

namespace Modules\Product\Models;

use App\Base\Model;

/**
 * Class Unit
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $code
 * @property string $name
 */
class Unit extends Model
{
    protected $table = 'units';
}