<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUniqueToStoreSkus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('store_skus', function (Blueprint $table) {
            $table->dropUnique('code');
            $table->unique(['tenant_id', 'sku_id_origin', 'sku_id', 'store_id']);
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
