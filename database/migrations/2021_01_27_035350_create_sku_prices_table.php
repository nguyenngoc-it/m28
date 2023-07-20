<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSkuPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sku_prices', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('merchant_id')->index();
            $table->integer('sku_id')->index();
            $table->double('cost_price', 18, 6)->nullable()->comment('Giá nhập');
            $table->double('wholesale_price', 18, 3)->nullable()->comment('Giá bán buôn');
            $table->double('retail_price', 18, 3)->comment('Giá bán lẻ');
            $table->timestamps();

            $table->unique(['merchant_id', 'sku_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sku_prices');
    }
}
