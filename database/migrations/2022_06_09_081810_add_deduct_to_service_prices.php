<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeductToServicePrices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('service_prices', function (Blueprint $table) {
            $table->unsignedFloat('deduct', 8, 3)->after('yield_price')->nullable()->comment('khấu trừ');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('service_prices', function (Blueprint $table) {
            //
        });
    }
}
