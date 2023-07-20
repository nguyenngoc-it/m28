<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServicePriceIdsToStorageFeeSkuStatistics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('storage_fee_sku_statistics', function (Blueprint $table) {
            $table->dropColumn('service_price_id');
            $table->json('service_price_ids')->after('warehouse_area_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('storage_fee_sku_statistics', function (Blueprint $table) {
            //
        });
    }
}
