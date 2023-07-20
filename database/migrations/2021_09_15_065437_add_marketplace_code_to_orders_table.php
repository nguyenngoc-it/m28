<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMarketplaceCodeToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('store_id')->after('merchant_id')->nullable()->index();
            $table->string('marketplace_code')->after('store_id')->nullable()->comment('SHOPEE, LAZADA, ...');
            $table->string('marketplace_store_id')->after('marketplace_code')->nullable()->comment('Định danh của store trên marketplace');

            $table->index(['marketplace_store_id', 'marketplace_code'], 'marketplace_store_id');
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
