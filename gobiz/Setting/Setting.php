<?php

namespace Gobiz\Setting;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $primaryKey = 'key';

    protected $fillable = ['*'];

    protected $guarded = [];

    protected $casts = [
        'value' => 'json',
    ];
}