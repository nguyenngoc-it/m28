<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSnapshotSkusToStorageFeeMerchantStatistics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('storage_fee_merchant_statistics', function (Blueprint $table) {
            $table->json('snapshot_skus')->after('total_sku')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('storage_fee_merchant_statistics', function (Blueprint $table) {
            //
        });
    }
}
