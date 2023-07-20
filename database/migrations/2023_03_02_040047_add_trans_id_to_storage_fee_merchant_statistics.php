<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTransIdToStorageFeeMerchantStatistics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('storage_fee_merchant_statistics', function (Blueprint $table) {
            $table->string('trans_id', 255)->after('trans_m4_id')->nullable()->unique();
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
