<?php

namespace Gobiz\Database;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Jenssegers\Mongodb\Schema\Builder;

abstract class MongoMigration extends Migration
{
    protected $connection = 'mongodb';

    /**
     * @return Builder|\Illuminate\Database\Schema\Builder
     */
    protected function schema()
    {
        return Schema::connection($this->getConnection());
    }
}