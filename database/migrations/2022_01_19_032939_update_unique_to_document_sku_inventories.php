<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUniqueToDocumentSkuInventories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('document_sku_inventories', function (Blueprint $table) {
            $table->unsignedInteger('warehouse_area_id')->nullable()->change();
            $table->dropUnique('document_id');
            $table->unique(['document_id','sku_id','warehouse_area_id'], 'document_sku_area');
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
