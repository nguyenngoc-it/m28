<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductMerchantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_merchants', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('product_id')->index();
            $table->integer('merchant_id')->index();
            $table->timestamps();

            $table->unique(['product_id', 'merchant_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->text('ubox_product_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_merchants');

        Schema::table("products", function (Blueprint $table){
            $table->dropColumn('ubox_product_code');
        });
    }
}
