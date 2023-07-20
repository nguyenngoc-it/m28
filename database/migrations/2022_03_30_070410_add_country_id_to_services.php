<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCountryIdToServices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->unsignedInteger('country_id')->after('tenant_id')->nullable()->comment('id thi truong ap dung')->index();
        });
        Schema::table('service_prices', function (Blueprint $table) {
            $table->unsignedInteger('country_id')->after('tenant_id')->nullable()->comment('id thi truong ap dung')->index();
        });
        Schema::table('stocks', function (Blueprint $table) {
            $table->double('total_storage_fee',16, 2)->after('real_quantity')->nullable()->comment('tổng phí lưu kho của sku')->index();
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
