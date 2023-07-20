<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductIdOriginToStoreSkus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('store_skus', function (Blueprint $table) {
            $table->unsignedInteger('product_id')->after('sku_id')->nullable()->default(0);
            $table->string('sku_id_origin')->after('product_id')->nullable()->comment('id sku trên marketplace');
            $table->string('product_id_origin')->after('sku_id_origin')->nullable()->comment('id product trên marketplace');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('store_skus', function (Blueprint $table) {
            //
        });
    }
}
