<?php

use Gobiz\Database\MongoMigration;
use Illuminate\Database\Schema\Blueprint;

class CreateTransactionsCollection extends MongoMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema()->collection('transactions', function (Blueprint $collection) {
            $collection->index('status');
            $collection->index('account_id');
            $collection->index('createdAt');
            $collection->index('response.id', null, ['sparse' => true]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
