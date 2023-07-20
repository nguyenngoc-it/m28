<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServicePriceIdToProductServices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('product_services', 'product_service_prices');
        Schema::table('product_service_prices', function (Blueprint $table) {
            $table->dropColumn('service_code');
            $table->unsignedInteger('tenant_id')->default(0)->after('product_id')->index();
            $table->unsignedInteger('service_id')->default(0)->after('tenant_id')->index();
            $table->unsignedInteger('service_price_id')->default(0)->after('tenant_id')->index();
            $table->unique(['tenant_id', 'service_id', 'service_price_id', 'product_id'], 'four_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_services', function (Blueprint $table) {
            //
        });
    }
}
