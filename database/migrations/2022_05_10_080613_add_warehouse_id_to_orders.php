<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWarehouseIdToOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('warehouse_id')->index()->nullable();
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->integer('warehouse_id')->index()->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['warehouse_id']);
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['warehouse_id']);
        });
    }
}
