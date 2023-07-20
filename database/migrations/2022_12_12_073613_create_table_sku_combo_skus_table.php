<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableSkuComboSkusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sku_combo_skus', function (Blueprint $table) {
            $table->id();
            $table->integer('sku_combo_id');
            $table->integer('sku_id');
            $table->integer('quantity')->comment('Số lượng của 1 sku');
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
        Schema::table('sku_combo_skus', function (Blueprint $table) {
            //
        });
    }
}
