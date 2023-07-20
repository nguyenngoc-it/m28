<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_stocks', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->index();
            $table->integer('order_id')->index();
            $table->integer('stock_id');
            $table->integer('sku_id')->index();
            $table->integer('warehouse_id')->index();
            $table->integer('warehouse_area_id')->index();
            $table->integer('changing_stock_id')->index();
            $table->integer('quantity');
            $table->integer('creator_id')->index();
            $table->timestamps();

            $table->unique(['stock_id', 'order_id']);
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
