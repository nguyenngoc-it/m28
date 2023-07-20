<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderSkuComboSkusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_sku_combo_skus', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id')->default(0);
            $table->integer('sku_id')->default(0);
            $table->integer('sku_combo_id')->default(0);
            $table->integer('quantity')->default(0);
            $table->index(['order_id', 'sku_id', 'sku_combo_id']);
            $table->double('price')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_sku_combo_skus');
    }
}
