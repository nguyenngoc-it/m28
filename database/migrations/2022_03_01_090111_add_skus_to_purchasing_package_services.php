<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSkusToPurchasingPackageServices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchasing_package_services', function (Blueprint $table) {
            $table->json('skus')->nullable()->default(null)->comment('Những skus áp dụng dịch vụ');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchasing_package_services', function (Blueprint $table) {
            //
        });
    }
}
