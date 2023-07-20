<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToStorageFeeSkuStatistics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('storage_fee_sku_statistics', function (Blueprint $table) {
            $table->string('merchant_username')->after('merchant_id')->nullable();
            $table->string('merchant_name')->after('merchant_username')->nullable();
            $table->string('sku_code')->after('sku_id')->nullable();
            $table->string('warehouse_code')->after('warehouse_id')->nullable();
            $table->string('warehouse_area_code')->after('warehouse_area_id')->nullable();
            $table->double('service_price',16,2)->after('service_price_ids')->nullable()->default(0);
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
