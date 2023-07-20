<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWarehouseAreaMerchantTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('warehouse_area_merchant', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('warehouse_area_id')->index();
            $table->integer('merchant_id')->index();
            $table->unique(['warehouse_area_id', 'merchant_id']);
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
        Schema::dropIfExists('warehouse_area_merchant');
    }
}
