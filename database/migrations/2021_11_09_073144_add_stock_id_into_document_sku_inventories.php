<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStockIdIntoDocumentSkuInventories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('document_sku_inventories', function (Blueprint $table) {
            $table->unsignedInteger('stock_id')->after('sku_id')->default(0)->index();
            $table->unsignedInteger('warehouse_id')->after('stock_id')->default(0)->index();
            $table->unsignedInteger('warehouse_area_id')->after('warehouse_id')->default(0)->index();
        });
        Schema::table('document_sku_importings', function (Blueprint $table) {
            $table->unique(['document_id', 'sku_id', 'stock_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('document_sku_inventories', function (Blueprint $table) {
            //
        });
    }
}
