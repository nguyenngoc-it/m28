<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id')->index();
            $table->integer('product_id')->index();
            $table->integer('sku_id')->index();
            $table->integer('warehouse_id')->index();
            $table->integer('warehouse_area_id');
            $table->integer('quantity');
            $table->integer('real_quantity');
            $table->timestamps();

            $table->unique(['warehouse_area_id', 'sku_id']);
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
