<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPriorityToOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('priority')->default(false);
        });

        Schema::table('order_packings', function (Blueprint $table) {
            $table->boolean('priority')->default(false);
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
            $table->dropColumn(['priority']);
        });

        Schema::table('order_packings', function (Blueprint $table) {
            $table->dropColumn(['priority']);
        });
    }
}
