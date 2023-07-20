<?php

namespace Modules\Currency\Models;

use App\Base\Model;
/**
 * Class Currency
 *
 * @property int $id
 * @property string $code
 * @property float $precision
 * @property string $format
 * @property string $thousands_separator
 * @property string $decimal_separator
 */
class Currency extends Model
{
    protected $table = 'currencies';
}