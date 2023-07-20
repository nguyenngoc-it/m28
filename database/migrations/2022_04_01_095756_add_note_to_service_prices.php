<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNoteToServicePrices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('service_prices', function (Blueprint $table) {
            $table->string('note', 255)->after('price')->nullable();
            $table->dropIndex('service_prices_tenant_id_service_code_service_code_price_unique');
            $table->dropColumn('service_code_price');
            $table->unique(['tenant_id','service_code','price']);
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
