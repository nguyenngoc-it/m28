<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAutoPriceByToServices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('auto_price_by', 100)->after('type')->nullable()->index()->comment('Tự động tính phí lưu kho theo logic nào');
        });
        Schema::table('service_prices', function (Blueprint $table) {
            $table->double('height', 10,3)->after('note')->nullable();
            $table->double('width', 10,3)->after('note')->nullable();
            $table->double('length', 10,3)->after('note')->nullable();
            $table->double('volume', 10,4)->after('note')->nullable();
            $table->json('seller_refs')->after('note')->nullable()->comment('mã giới thiệu của seller');
            $table->json('seller_codes')->after('note')->nullable()->comment('mã seller');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            //
        });
    }
}
