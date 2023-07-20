<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuantityInStockBeforeBalancedToDocumentSkuInventories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('document_sku_inventories', function (Blueprint $table) {
            $table->integer('quantity_in_stock_before_balanced')->nullable()->after('quantity_in_stock')->comment('Số lượng tồn kho trước khi cân bằng');
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
